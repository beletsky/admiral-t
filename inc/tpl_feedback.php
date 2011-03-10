<?php


include_once( PATH_TO_ROOT . 'inc/message.class.php' );
include_once( PATH_TO_ROOT . 'inc/user_tpl.class.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_object.php' );


function proceed_feedback( &$page )
{
    global $g_catalog;

    $body = '';

    $id = $g_catalog->get_next_param( 0 );

    if( is_string( $id ) && $id == 'success' )
    {
        $g_catalog->path_add( 'Сообщение отправлено', 'success' );

        $page = new StaticPage( $page->page_code() . '-success' );
        return proceed_static_page( $page, false );
    }
    else if( !$id )
    {
        $body .= proceed_static_page( $page, false );
        
        // Отобразить форму.
        $tpl = new Template();
        $tpl->set_file( 'l', PATH_TO_TPL . 'feedback.tpl' );
        $tpl->set_block( 'l', 'form_', 'form__' );
        
        $page_url = $g_catalog->path_url( $g_catalog->get_path_to( $page->id() ) );
        $tpl->set_var( 'URL', $page_url );
        $page->place( $tpl, 'page_' );
        
        form_feedback( $page_url, $tpl, 'form_', $page_url . '-success' );

        $body .= $tpl->parse( 'c', 'l' );
    }
    else redirect( 303, $url );
    
    return $body;
}


function form_feedback( $url, &$tpl, $block, $success_url = '' )
{
    global $g_sites, $form;
    
    $body = '';

    $form_own_data = ( isset( $form[ 'Form' ] ) 
        && !strcasecmp( $form[ 'Form' ], $block ) );
    $data = $form_own_data ? $form : array();
        
    $errors = array();
    $fields = array(
        'Name'      => 'Укажите Ваше имя.',
        'Email'     => 'Укажите Ваш адрес электронной почты.',
//        'Phone'     => '',
        'Text'      => 'Введите текст Вашего сообщения.',
//        'Position'  => '',
//        'Details'   => '',
    );
    
    if( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' && $form_own_data )
    {
        // Проверить заполненность полей.
        foreach( $fields as $k => $v )
        {
            if( ( !isset( $data[ $k ] ) || !$data[ $k ] ) && $v )
            {
                $errors[] = $v;
            }
            else if( !isset( $data[ $k ] ) ) $data[ $k ] = '';
        }
        
        // Проверить правильность адреса электронной почты.
        if( $data[ 'Email' ] && !check_email( $data[ 'Email' ] ) )
        {
            $errors[] = 'Укажите правильный адрес электронной почты!';
        }

        // Проверить правильность ввода каптчи.
        if(    !isset( $_SESSION[ $block . 'captcha_keystring' ] ) 
            || $_SESSION[ $block . 'captcha_keystring' ] !=  $data[ 'Captcha' ] )
        {
            $errors[] = 'Неправильно указан проверочный код!';
        }
        
        // Проверить правильность закачки файлов.
//        if(    isset( $_FILES[ 'file' ][ 'tmp_name' ] ) 
//            && $_FILES[ 'file' ][ 'error' ]
//            && $_FILES[ 'file' ][ 'error' ] != UPLOAD_ERR_NO_FILE )
//        {
//            if(    $_FILES[ 'file' ][ 'error' ] == UPLOAD_ERR_INI_SIZE
//                || $_FILES[ 'file' ][ 'error' ] == UPLOAD_ERR_FORM_SIZE )
//            {
//                $errors[] = 'Вы указываете слишком большой файл!';
//                $errors[] = 'Выберите что-нибудь поменьше размером.';
//            }
//            else
//            {
//                $errors[] = 'Файл был загружен с ошибками!';
//                $errors[] = 'Повторите, пожалуйста, отправку сообщения.';
//            }
//        }
        
        // Если ошибок при вводе не было, отправить сообщение.
        if( !$errors )
        {
            // Сделать требуемые преобразования полей.
            $tpl_msg = new Template();
            $msg = new UserTPL( 'message-feedback' );
            $msg->set_template( $tpl_msg, 'm' );

            $tpl_msg->set_var( 'HOST', $g_sites[ HOST_CODE ][ 'Host' ] );
            $tpl_msg->set_var( 'DATE', date( 'd.m.Y', time() ) );
            $tpl_msg->set_var( 'TIME', date( 'H:i:s', time() ) );
            $tpl_msg->set_var( 'NAME', $data[ 'Name' ] );
            $tpl_msg->set_var( 'EMAIL', $data[ 'Email' ] );
//            $tpl_msg->set_var( 'PHONE', $data[ 'Phone' ] );
            $tpl_msg->set_var( 'TEXT', strip_tags( $data[ 'Text' ] ) );
//            $tpl_msg->set_var( 'POSITION', $data[ 'Position' ] );
//            $tpl_msg->set_var( 'DETAILS', $data[ 'Details' ] );
            
            // Создать сообщение.
            $msg = new Message( $tpl_msg->parse( 'c', 'm' ) );

            // Если есть прикрепленные файлы.
//            if(    isset( $_FILES[ 'file' ][ 'tmp_name' ] ) 
//                && $_FILES[ 'file' ][ 'tmp_name' ]
//                && is_uploaded_file( $_FILES[ 'file' ][ 'tmp_name' ] )
//                && !$_FILES[ 'file' ][ 'error' ] )
//            {
//                $msg->attach( $_FILES[ 'file' ][ 'tmp_name' ], 
//                    $_FILES[ 'file' ][ 'name' ] );
//            }
            
            // Отослать сообщение.
            if( $msg->send() )
            {
                // Отобразить страницу об успешной отправке сообщения.
                redirect( 303, $success_url ? $success_url : $url );
                exit();
            }
            else
            {
                $errors[] = 'При отправке сообщения произошла ошибка!'
                    . ' Попробуйте ещё раз.';
            }
        }
        
        // Иначе были ошибки,
        // надо попасть в общий код отображения формы добавления.
    }

    if( $url == '/' ) $tpl->set_var( strtoupper( $block . 'URL' ), $url );
    else $tpl->set_var( strtoupper( $block . 'URL' ), $url . HTML_EXTENSION );
    $tpl->set_var( strtoupper( $block . 'CAPTCHA_URL' ), get_captcha_url( $block ) );

    // Выставить значения полей в прежние (или пустые) значения.
    foreach( $fields as $k => $v )
    {
        set_form_var( $data[ $k ], '' );
        $tpl->set_var( strtoupper( $block . $k ), $data[ $k ] );
    }
                
    $tpl->set_var( strtoupper( $block . 'ERRORS' ), 
        implode( '<br />', $errors ) );

    $tpl->parse( $block . '_', $block );
            
    return;
}


?>
