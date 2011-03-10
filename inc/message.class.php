<?php


define( 'RFC_EMAIL_CRLF', "\n" );


class Message
{
    var $m_to;
    var $m_subject;
    var $m_headers = array();
    var $m_text;
    var $m_boundary;
    var $m_attachments = array();

    function Message( $text )
    {
        $this->m_boundary = md5( time() );
    
        // Русский текст кодируется в KOI8-R.
//        $mail = iconv( 'UTF-8', 'KOI8-R', $text );
        $mail = $text;
        
        // Разделить сообщение на части заголовков и текста.
        $parts = array();
        preg_match( '/(.*?\r\n)\r\n(.*)/s', $mail, $parts );
        
        $this->m_text = str_replace( "\r\n", RFC_EMAIL_CRLF, $parts[ 2 ] );
            
        $this->m_headers = explode( "\r\n", $parts[ 1 ] );

        // Выделить нужные для работы заголовки.        
        $this->m_to = $this->get_header( 'To', true );
        $this->m_subject = '=?UTF-8?B?' . base64_encode( $this->get_header( 'Subject', true ) ) . '?=';
        
        // Перекодировать оставшиеся заголовки.
        $this->encode_non_ascii_headers();
    }

    // Возвращает текущую тему сообщения и устанавливает новую, если та указана.
    function subject( $subject = NULL )
    {
        $result = $this->m_subject;
        if( isset( $subject ) ) $this->m_subject = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';
        return $result;
    }

    // Возвращает текущего получателя сообщения и устанавливает нового, если тот указан.
    function to( $to = NULL )
    {
        $result = $this->m_to;
        if( isset( $to ) ) $this->m_to = $to;
        return $result;
    }
    
    // Добавляет в сообщение файл.
    function attach( $file, $name = '', $content_type = 'application/octet-stream' )
    {
        $h = fopen( $file, 'rb' );
        $contents = fread( $h, filesize( $file ) );
        fclose( $h );

//        $this->m_text .= $file . RFC_EMAIL_CRLF;
//        $this->m_text .= realpath( $file ) . RFC_EMAIL_CRLF;
//        $this->m_text .= getcwd() . RFC_EMAIL_CRLF;
              
        $this->m_attachments[] = array( 
            'type'  => $content_type, 
            'name'  => basename( $name ? $name : $file ),
            'lines' => chunk_split( base64_encode( $contents ) ) );
    }

    // Отсылает сообщение. Возвращает true в случае успеха, false при неудаче.
    function send()
    {
        $msg = '';
        if( $this->m_attachments )
        {
            $headers = implode( RFC_EMAIL_CRLF, $this->m_headers )
               . 'MIME-Version: 1.0' . RFC_EMAIL_CRLF
               . 'Content-Type: multipart/related; boundary="' 
               . $this->m_boundary . '"' . RFC_EMAIL_CRLF;
            
            // Текстовая часть.
            $msg .= "--" . $this->m_boundary . RFC_EMAIL_CRLF;
            $msg .= 'Content-Type: text/plain; charset=utf-8' . RFC_EMAIL_CRLF;
            $msg .= 'Content-Transfer-Encoding: 8bit' . RFC_EMAIL_CRLF;
            $msg .= RFC_EMAIL_CRLF;
            $msg .= $this->m_text . RFC_EMAIL_CRLF;
            $msg .= RFC_EMAIL_CRLF;

            // Вложения.
            foreach( $this->m_attachments as $v )
            {
                $msg .= "--" . $this->m_boundary . RFC_EMAIL_CRLF;
                $msg .= 'Content-Type: ' . $v[ 'type' ] . '; name="' . $v[ 'name' ] . '"' . RFC_EMAIL_CRLF;
                $msg .= 'Content-Transfer-Encoding: base64' . RFC_EMAIL_CRLF;
                $msg .= 'Content-Disposition: attachment; filename="' . $v[ 'name' ] . '"' . RFC_EMAIL_CRLF;
                $msg .= RFC_EMAIL_CRLF;
                $msg .= $v[ 'lines' ] . RFC_EMAIL_CRLF;
                $msg .= RFC_EMAIL_CRLF;
            }
            
            // Закрыть части.
            $msg .= '--' . $this->m_boundary . '--' . RFC_EMAIL_CRLF;
            $msg .= RFC_EMAIL_CRLF;
        }
        else
        {
            $headers = implode( RFC_EMAIL_CRLF, $this->m_headers )
                . 'Content-Type: text/plain; charset=utf-8' . RFC_EMAIL_CRLF;
                
            $msg = $this->m_text;
        }

//        print_r( $headers );
//        print_r( $msg );
//        return true;
//        file_put_contents( '1.txt', $msg );
        
        // the INI lines are to force the From Address to be used !
        ini_set( 'sendmail_from', $this->get_header( 'From' ) );  
        $result = mail( $this->m_to, $this->m_subject, $msg, $headers );
        ini_restore( 'sendmail_from' );
        usleep( 500000 );
        
        return $result;
    }
    
    // private
    // Выделяет из заголовков указанное поле и возвращает его значение.
    // !!! Ограничение!!! Только однострочные заголовки!
    function get_header( $header, $cut = false )
    {
        foreach( $this->m_headers as $k => $v )
        {
            // Найти заголовок в списке заголовков.
            $match = array();
            if( !preg_match( '/^' . $header . ':\s+(.*)/i', $v, $match ) ) continue;
            
            // Удалить заголовок из списка.
            if( $cut ) unset( $this->m_headers[ $k ] );
                
            // Вернуть значение заголовка.
            return $match[ 1 ];
        }
        
        return '';
    }
    
    // private
    // Кодирует не-ASCII символы в заголовках.
    function encode_non_ascii_headers()
    {
        foreach( $this->m_headers as $k => $v )
        {
            // Выделить содержимое заголовка.
            $match = array();
            if( !preg_match( '/^(.*?):\s+(.*)/i', $v, $match ) ) continue;

//            $this->m_headers[ $k ] = $match[ 1 ] . ': ' 
//                . quoted_printable_encode( $match[ 2 ] );
            $this->m_headers[ $k ] = $match[ 1 ] . ': ' 
                . quoted_base64( $match[ 2 ] );
        }
    }
}


if( !function_exists( 'quoted_printable_encode' ) )
{
    function quoted_printable_encode( $str )
    {
        $res = '';
        $len = strlen( $str );
        for( $i=0; $i<$len; $i++ )
        {
            $ch = $str[ $i ];
            if( ctype_print( $ch ) && !ctype_punct( $ch ) ) $res .= $ch;
            else $res .= sprintf( '=%02X', ord( $char ) );
        }
        return $res;
    }
}


function quoted_base64( $str )
{
    $len = strlen( $str );
    
    $result = '';
    $convert = '';
    $in_convert = false;
    
    for( $k = 0; $k < $len; ++$k )
    {
        $ch = $str[ $k ];
        $ord = ord( $ch );
        
        if( $in_convert )
        {
            if( $ord > 127 ) $convert .= $ch;
            else
            {
                $result .= '=?koi8-r?B?' . base64_encode( $convert ) . '?=' . $ch;
                $convert = '';
                $in_convert = false;
            }
        }
        else
        {
            if( $ord <= 127 ) $result .= $ch;
            else
            {
                $convert = $ch;
                $in_convert = true;
            }
        }
    }
    if( $in_convert ) $result .= '=?koi8-r?B?' . base64_encode( $convert ) . '?=';
    
    return $result;
}


?>
