<?php

if(!$telegram->is_chat_group()){ return; }

if($telegram->text_has(["pole", "subpole", "bronce"], TRUE) or $telegram->text_command("pole") or $telegram->text_command("subpole")){
    $this->analytics->event("Telegram", "Pole");
    $pole = $pokemon->settings($telegram->chat->id, 'pole');
    if($pole != NULL && $pole == FALSE){ return; }
    if($pokemon->settings($telegram->user->id, 'no_pole') == TRUE){ return; }

    // Si está el Modo HARDCORE, la pole es cada hora. Si no, cada día.
    $timer = ($pokemon->settings($telegram->chat->id, 'pole_hardcore') ? "H" : "d");

    if(!empty($pole)){
        $pole = unserialize($pole);
        if(
            ( $telegram->text_has("pole", TRUE) &&    is_numeric($pole[0]) && date($timer) == date($timer, $pole[0]) ) or
            ( $telegram->text_has("subpole", TRUE) && is_numeric($pole[1]) && date($timer) == date($timer, $pole[1]) ) or
            ( $telegram->text_has("bronce", TRUE) &&  is_numeric($pole[2]) && date($timer) == date($timer, $pole[2]) )
        ){
            return;  // Mismo dia? nope.
        }
    }
    $pole_user = unserialize($pokemon->settings($telegram->chat->id, 'pole_user'));
    $pkuser = $pokemon->user($telegram->user->id);
    if($pkuser){
        $timeuser = $pokemon->settings($pkuser->telegramid, 'lastpole');
        if(empty($timeuser)){ $timeuser = 0; }
    }

    if($telegram->text_has("pole", TRUE)){ // and date($timer) != date($timer, $pole[0])
        $pole = [time(), NULL, NULL];
        $pole_user = [$telegram->user->id, NULL, NULL];
        $action = "la *pole*";
        if($pkuser && $timer == "d"){
            if(date("d") != $timeuser){
                $pokemon->update_user_data($pkuser->telegramid, 'pole', ($pkuser->pole + 3));
                $pokemon->settings($pkuser->telegramid, 'lastpole', date("d"));
            }
        }
    }elseif($telegram->text_has("subpole", TRUE) and date($timer) == date($timer, $pole[0]) and $pole_user[1] == NULL){
        if(in_array($telegram->user->id, $pole_user)){ return; } // Si ya ha hecho pole, nope.
        $pole[1] = time();
        $pole_user[1] = $telegram->user->id;
        $action = "la *subpole*";
        if($pkuser && $timer == "d"){
            if(date("d") != $timeuser){
                $pokemon->update_user_data($pkuser->telegramid, 'pole', ($pkuser->pole + 2));
                $pokemon->settings($pkuser->telegramid, 'lastpole', date("d"));
            }
        }
    }elseif($telegram->text_has("bronce", TRUE) and date($timer) == date($timer, $pole[0]) and $pole_user[1] != NULL and $pole_user[2] == NULL){
        if(in_array($telegram->user->id, $pole_user)){ return; } // Si ya ha hecho sub/pole, nope.
        $pole[2] = time();
        $pole_user[2] = $telegram->user->id;
        $action = "el *bronce*";
        if($pkuser && $timer == "d"){
            if(date("d") != $timeuser){
                $pokemon->update_user_data($pkuser->telegramid, 'pole', ($pkuser->pole + 1));
                $pokemon->settings($pkuser->telegramid, 'lastpole', date("d"));
            }
        }
    }else{
        return;
    }

    $pokemon->settings($telegram->chat->id, 'pole', serialize($pole));
    $pokemon->settings($telegram->chat->id, 'pole_user', serialize($pole_user));
    $telegram->send->text($telegram->user->first_name ." ha hecho $action!", TRUE)->send();
    // $telegram->send->text("Lo siento " .$telegram->user->first_name .", pero hoy la *pole* es mía! :D", TRUE)->send();
    return;
}

if($telegram->text_command("polerank") or $telegram->text_has("!polerank")){
    $poleuser = $pokemon->settings($telegram->chat->id, 'pole_user');
    $pole = $pokemon->settings($telegram->chat->id, 'pole');

    if($pole == FALSE){ return; }
    if($pole == NULL or ($pole === TRUE or $pole === 1)){
        $telegram->send
            ->text("Nadie ha hecho la *pole*.", TRUE)
        ->send();
        return;
    }

    $pole = unserialize($pole);
    $poleuser = unserialize($poleuser);
    $hardcore = $pokemon->settings($telegram->chat->id, 'pole_hardcore');

    $str = $telegram->emoji(":warning:") ." *Pole ";
    $str .= ($hardcore ? "de las " .date("H", $pole[0]) ."h" : "del " .date("d", $pole[0])) ."*:\n\n";

    foreach($poleuser as $n => $u){
        $ut = $telegram->emoji(":question-red:");
        $points = NULL;
        if(!empty($u)){
            $user = $pokemon->user($u);
            $ut = (!empty($user->username) ? $user->username : $user->telegramuser);
            $points = $user->pole;
        }

        $str .= $telegram->emoji(":" .($n + 1) .": ") .$ut .($points ? " (*$points*)" : "") ."\n";
    }

    $telegram->send
        ->text($str, TRUE)
    ->send();
    return;
}

?>
