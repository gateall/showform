<?php
if (!defined('_GNUBOARD_')) exit;

function empty_mb_id($reg_mb_id)
{
    if (trim($reg_mb_id)=='')
        return "???뜚?袁⑹뵠?遺? ??낆젾??雅뚯눘???뽰궎.";
    else
        return "";
}

function valid_mb_id($reg_mb_id)
{
    $reg_mb_id = trim($reg_mb_id);

    if ($reg_mb_id === '')
        return "";

    if (strpos($reg_mb_id, '@') !== false) {
        if (!preg_match('/^[0-9a-zA-Z._%+\-]+@[0-9a-zA-Z.\-]+\.[A-Za-z]{2,}$/', $reg_mb_id))
            return "???뜚?袁⑹뵠????李??깍폒????而?몴?우쓺 ??낆젾??뤾쉭??";
    } else {
        if (preg_match("/[^0-9a-z_]+/i", $reg_mb_id))
            return "???뜚?袁⑹뵠?遺얜뮉 ?怨론?? ??ъ쁽, _ 筌???낆젾??뤾쉭??";
    }

    return "";
}

function count_mb_id($reg_mb_id)
{
    if (strlen($reg_mb_id) < 3)
        return "???뜚?袁⑹뵠?遺얜뮉 筌ㅼ뮇??3疫꼲????곴맒 ??낆젾??뤾쉭??";
    else
        return "";
}

function exist_mb_id($reg_mb_id)
{
    global $g5;

    $reg_mb_id = trim($reg_mb_id);
    if ($reg_mb_id == "") return "";

    $sql = " select count(*) as cnt from `{$g5['member_table']}` where mb_id = '$reg_mb_id' ";
    $row = sql_fetch($sql);
    if ($row['cnt'])
        return "??? ???쒍빳臾믪뵥 ???뜚?袁⑹뵠????낅빍??";
    else
        return "";
}

function reserve_mb_id($reg_mb_id)
{
    global $config;
    if (preg_match("/[\,]?{$reg_mb_id}/i", $config['cf_prohibit_id']))
        return "??? ??됰튋????λ선嚥??????????용뮉 ???뜚?袁⑹뵠????낅빍??";
    else
        return "";
}

function empty_mb_nick($reg_mb_nick)
{
    if (!trim($reg_mb_nick))
        return "??곌퐬?袁⑹뱽 ??낆젾??雅뚯눘???뽰궎.";
    else
        return "";
}

function valid_mb_nick($reg_mb_nick)
{
    if (!check_string($reg_mb_nick, G5_HANGUL + G5_ALPHABETIC + G5_NUMERIC))
        return "??곌퐬?袁? ?⑤벉媛??곸뵠 ???, ?怨론? ??ъ쁽筌???낆젾 揶쎛?館鍮??덈뼄.";
    else
        return "";
}

function count_mb_nick($reg_mb_nick)
{
    if (strlen($reg_mb_nick) < 4)
        return "??곌퐬?袁? ??? 2疫꼲?? ?怨론?4疫꼲????곴맒 ??낆젾 揶쎛?館鍮??덈뼄.";
    else
        return "";
}

function exist_mb_nick($reg_mb_nick, $reg_mb_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt from {$g5['member_table']} where mb_nick = '$reg_mb_nick' and mb_id <> '$reg_mb_id' ");
    if ($row['cnt'])
        return "??? 鈺곕똻???롫뮉 ??곌퐬?袁⑹뿯??덈뼄.";
    else
        return "";
}

function reserve_mb_nick($reg_mb_nick)
{
    global $config;
    if (preg_match("/[\,]?".preg_quote($reg_mb_nick)."/i", $config['cf_prohibit_id']))
        return "??? ??됰튋????λ선嚥??????????용뮉 ??곌퐬????낅빍??";
    else
        return "";
}

function empty_mb_email($reg_mb_email)
{
    if (!trim($reg_mb_email))
        return "E-mail 雅뚯눘?쇘몴???낆젾??雅뚯눘???뽰궎.";
    else
        return "";
}

function valid_mb_email($reg_mb_email)
{
    if (!preg_match("/([0-9a-zA-Z_-]+)@([0-9a-zA-Z_-]+)\.([0-9a-zA-Z_-]+)/", $reg_mb_email))
        return "E-mail 雅뚯눘?쇔첎? ?類ㅻ뻼??筌띿쉸? ??녿뮸??덈뼄.";
    else
        return "";
}

// 疫뀀뜆? 筌롫뗄???袁⑥컭??野꺜??
function prohibit_mb_email($reg_mb_email)
{
    global $config;

    list($id, $domain) = explode("@", $reg_mb_email);
    $email_domains = explode("\n", trim($config['cf_prohibit_email']));
    $email_domains = array_map('trim', $email_domains);
    $email_domains = array_map('strtolower', $email_domains);
    $email_domain = strtolower($domain);

    if (in_array($email_domain, $email_domains))
        return "$domain 筌롫뗄??? ?????????곷뮸??덈뼄.";

    return "";
}

function exist_mb_email($reg_mb_email, $reg_mb_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt from `{$g5['member_table']}` where mb_email = '$reg_mb_email' and mb_id <> '$reg_mb_id' ");
    if ($row['cnt'])
        return "??? ???쒍빳臾믪뵥 E-mail 雅뚯눘???낅빍??";
    else
        return "";
}

function empty_mb_name($reg_mb_name)
{
    if (!trim($reg_mb_name))
        return "??已????낆젾??雅뚯눘???뽰궎.";
    else
        return "";
}

function valid_mb_name($mb_name)
{
    if (!check_string($mb_name, G5_HANGUL))
        return "??已?? ?⑤벉媛??곸뵠 ???筌???낆젾 揶쎛?館鍮??덈뼄.";
    else
        return "";
}

function valid_mb_hp($reg_mb_hp)
{
    $reg_mb_hp = preg_replace("/[^0-9]/", "", $reg_mb_hp);
    if(!$reg_mb_hp)
        return "????怨뺤쓰?紐? ??낆젾??雅뚯눘???뽰궎.";
    else {
        if(preg_match("/^01[0-9]{8,9}$/", $reg_mb_hp))
            return "";
        else
            return "????怨뺤쓰?紐? ??而?몴?우쓺 ??낆젾??雅뚯눘???뽰궎.";
    }
}

function exist_mb_hp($reg_mb_hp, $reg_mb_id)
{
    global $g5;

    if (!trim($reg_mb_hp)) return "";

    $reg_mb_hp = hyphen_hp_number($reg_mb_hp);

    $sql = "select count(*) as cnt from {$g5['member_table']} where mb_hp = '$reg_mb_hp' and mb_id <> '$reg_mb_id' ";
    $row = sql_fetch($sql);

    if($row['cnt'])
        return " ??? ????餓λ쵐??????怨뺤쓰?紐꾩뿯??덈뼄. ".$reg_mb_hp;
    else
        return "";
}