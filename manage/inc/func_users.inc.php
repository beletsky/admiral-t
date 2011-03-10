<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/inc/func_users.inc.php                                               #
#   Изменения в таблице пользователей.                                         #
#                                                                              #
################################################################################
/*

  function user_adm_add()      - Функции для работы с пользователями - админами.
  function user_adm_edit()       Возвращают текст ошибки. 
  function user_adm_del()        Принимают на вход массив с параметрами - полями.
  
  function get_group_by_code() - Вернуть ID_Group по коду группы

*/

function user_adm_add($data) {
    global $db;
    $r = _user_adm_check(_user_prepare($data), true);
    if ($r == '') {
        $q  = 'insert into dwUsers set';
        $q .= ' ID_Group = ' . $data['ID_Group'] . ',';
        $q .= ' UserLogin = "' . addslashes($data['UserLogin']) . '",';
        $q .= ' UserPwd = "' . addslashes($data['UserPwd']) . '",';
        $q .= ' UserDeleted = ' . $data['UserDeleted'];
        $db->Query($q);
    }
    return $r;
}

function user_adm_edit($data) {
    global $db;
    $r = '';
    $r = _user_adm_check(_user_prepare($data));
    if ($r == '') {
        $q  = 'update dwUsers set';
        $q .= ' ID_Group = ' . $data['ID_Group'] . ',';
        $q .= ' UserLogin = "' . addslashes($data['UserLogin']) . '",';
        $q .= ' UserPwd = "' . addslashes($data['UserPwd']) . '",';
        $q .= ' UserDeleted = ' . $data['UserDeleted'];
        $q .= ' where ID_User = ' . $data['ID_User'];
        $db->Query($q);
    }
    return $r;
}

function user_adm_del($id) {
    global $db;
    $r = '';
    if (!_is_user_exists($id)) $r .= 'Пользователь не найден.<br>';
    if ($r == '') {
        $q = 'delete from dwUsers where ID_User = ' . $id;
        $db->Query($q);
    }
    return $r;
}

// Если isAdd, то проверяем существование польз. с таким логином, 
// иначе проверка на сущ. записи с таким ID и сущ. польз. с таким логином, но другим ID.
function _user_adm_check($data, $isAdd = false) {
    global $db, $g_user;
    $r = '';
    
    // Проверка наличия полей
    if ($data['UserLogin'] == '') $r .= 'Введите логин.<br>';
    if ($data['UserPwd'] == '') $r .= 'Введите пароль.<br>';
    // Проверка допустимости значений полей
    if ($r == '') {
        if (!$isAdd && !string_is_id($data['ID_User'])) $r .= 'Неверный ID.<br>';
        if (!string_is_login($data['UserLogin'])) $r .= 'Электронный адрес содержит недопустимые символы.<br>';
        if (!string_is_login($data['UserPwd'])) $r .= 'Пароль содержит недопустимые символы.<br>';
        if (!string_is_id($data['ID_Group'])) $r .= 'Группа не найдена.<br>';
        if (!$isAdd && $data['ID_User'] == $g_user->Get('ID_User')) {
            if ($data['UserDeleted']) $r .= 'Вы не можете удалить самого себя.<br>';
            if ($data['ID_Group'] != $g_user->Get('ID_Group')) $r .= 'Вы не можете изменить свою группу.<br>';
        }
    }
    // Если isAdd, проверим есть ли пользователь с таким логином, иначе проверим есть ли пользователь с таким логином, но другим ID
    if ($r == '') {
        $q = 'select ID_User from dwUsers where UserLogin = "' . $data['UserLogin'] . '"';
        if (!$isAdd) $q .= ' and ID_User <> ' . $data['ID_User'];
        $db->Query($q);
        if ($db->Nf()) $r .= 'Пользователь с таким электронным адресом уже зарегистрирован.<br>';
    }
    // Если не isAdd, проверим, существует ли запись с таким ID
    if ($r == '' && !$isAdd && !_is_user_exists($data['ID_User'])) $r .= 'Пользователь не найден.<br>';
    
    return $r;
}

function _user_prepare(&$data) {
    $data['ID_Group']        = get_group_by_code($data['GroupCode']);
    $data['UserDeleted']     = isset($data['UserDeleted']) && $data['UserDeleted'] != '' ? 1 : 0;
    $data['UserTypeCode']    = isset($data['UserTypeCode']) ? trim($data['UserTypeCode']) : '';
    $data['UserLogin']       = isset($data['UserLogin']) ? trim($data['UserLogin']) : '';
    $data['UserPwd']         = isset($data['UserPwd']) ? trim($data['UserPwd']) : '';
    $data['UserSecretCode']  = isset($data['UserSecretCode']) ? trim($data['UserSecretCode']) : '';
    $data['UserIP']          = isset($data['UserIP']) ? trim($data['UserIP']) : '';
    $data['UserFName']       = isset($data['UserFName']) ? trim($data['UserFName']) : '';
    $data['UserLName']       = isset($data['UserLName']) ? trim($data['UserLName']) : '';
    $data['UserMName']       = isset($data['UserMName']) ? trim($data['UserMName']) : '';
    $data['UserEmail']       = isset($data['UserEmail']) ? trim($data['UserEmail']) : '';
    $data['UserPwdQuestion'] = isset($data['UserPwdQuestion']) ? trim($data['UserPwdQuestion']) : '';
    $data['UserPwdAnswer']   = isset($data['UserPwdAnswer']) ? trim($data['UserPwdAnswer']) : '';
    $data['UserSubscribed']  = isset($data['UserSubscribed']) ? trim($data['UserSubscribed']) : '';
    $data['UserContacts']    = isset($data['UserContacts']) ? trim($data['UserContacts']) : '';
    $data['UserPhoto']       = isset($data['UserPhoto']) ? trim($data['UserPhoto']) : '';
    $data['UserExpiryDate']  = isset($data['UserExpiryDate']) ? trim($data['UserExpiryDate']) : '';
    $data['UserVisible']     = isset($data['UserVisible']) && $data['UserVisible'] != '' ? 1 : 0;
    $data['UserIsVoter']     = isset($data['UserIsVoter']) && $data['UserIsVoter'] != '' ? 1 : 0;
    $data['UserRegDate']     = isset($data['UserRegDate']) ? trim($data['UserRegDate']) : '';
    $data['UserRegDbDate']   = isset($data['UserRegDbDate']) ? trim($data['UserRegDbDate']) : '';
    $data['UserDataChecked'] = isset($data['UserDataChecked']) && $data['UserDataChecked'] != '' ? 1 : 0;
    return $data;
}

function _is_user_exists($id) {
    return is_obj_exists($id, 'ID_User', 'dwUsers');
}

function get_group_by_code($groupCode) {
    global $db;
    $r = 0;
    if (string_is_login($groupCode)) {
        $db->Query('select ID_Group from dwGroups where GroupCode = "' . $groupCode . '"');
        if ($db->NextRecord()) $r = $db->F('ID_Group');
    }
    return $r;
}

?>