<?php

// do値の日平均を登録する処理
// 毎日23:55に稼働し、当日を含む一週間平均値を計算・登録する
    //日付取得
    $org_date = date("Y-m-d");
    // mysql準備
    $mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'marukin');

    // 今日のデータ登録処理
    $sql = 'select days, max(times), avg(do) from data where days="'. $org_date. '" order by days desc;';
    echo $sql;
    $res = $mysqli->query($sql);    //sql実行
    while ($row = $res->fetch_array() ){
        if ($row[2] != ""){         // Null値の場合は取り出さない
        $today_set_time = $row[1];
        $today_set_do   = (float)$row[2];
        }
    }

    $sql = "select * from do_average order by days desc limit 7;";  //当日を含む七日分のデータ抽出用構文
    // $sql = "select * from do_average limit 30;";  //当日を含む七日分のデータ抽出用構文

    $res = $mysqli->query($sql);    //sql実行(DB平均値を取得)
    while ($row = $res->fetch_array() ){
        if ($row[2] != ""){         // Null値の場合は取り出さない
        $do_average_days[] = (float)$row[2];
        }
    }

    $result = array_sum($do_average_days) / count($do_average_days);  //7日間平均値

    //確認用
    echo "\n";
    //var_dump($do_average_days);
    echo "\n". $org_date. "\n". $today_set_time. "\n". $today_set_do. "\n". $result. "\n";
    //確認用

    // 登録用構文準備
    $call = 'replace into do_average value("%s","%s","%s","%s");';
    $sql = sprintf($call, $org_date, $today_set_time, $today_set_do, $result);
    //echo $sql;
    //echo "\n";
    $res = $mysqli->query($sql);


    $mysqli->close();
?>