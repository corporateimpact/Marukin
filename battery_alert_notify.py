#!/usr/bin/env python abort_voltage LINE alert
# coding=utf-8
# ----- 電圧異常時のＬＩＮＥアラート -----
import requests
import os
import time
import datetime
import mysql.connector

# -----データベースの情報を格納する定数-----
COMMON_DB_USER = "root"  # 共通DBのユーザ名
COMMON_DB_PASS = "pm#corporate1"  # 共通DBのパスワード
COMMON_DB_HOST = "localhost"  # 共通DBのホスト名
COMMON_DB_NAME = "common_db"  # 共通DBのDB名

# ---------------------------------------

# -----グローバル変数群-----
pj_name = "marukin"  # プロジェクト名
limit_tbl_item = None  # 規定値テーブルの項目名
current_value = None  # 既定値テーブルの現在値
smtp_addr = None  # 送信元アドレス
smtp = None  # 送信元接続情報
line_token = ""  # LINEトークン
common_pj = None  # 共通データベースへの接続情報を保持する変数
pj_con = None  # プロジェクトごとのデータベースへの接続情報を保持する変数
line_message = "<< マルキン アラート >>"  # LINE通知のメッセージタイトル
# alert_flg = "OFF"  # LINEアラートが発生したら"ON"
# --------------------------

# << file path >>
# <マルキンサーバ>
Path_batteryinfo = "/home/upload/infos/battery/batteryinfo.ini"
Path_alertinfo = "/home/upload/infos/battery/alertinfo.ini"

# << Battery Boundary >>
b_high_limit = 15.0
b_low_limit = 11.5

# << 30分前の時刻を取得 >>
#BEFORE_30min = format(datetime.datetime.fromtimestamp(time.time() - 1800))[:19]
BEFORE_30min = format(
    datetime.datetime.fromtimestamp(time.time() - 86400))[:19]
print("BEFORE_30min =" + BEFORE_30min)

# << Text-File Open >>
FILE_TIMESTAMP = datetime.datetime.fromtimestamp(
    os.path.getmtime(Path_batteryinfo))
print("FILE_TIMESTAMP =" + format(FILE_TIMESTAMP))

with open(Path_batteryinfo) as voltinfo:
    v_info = voltinfo.read().replace("\n", "")
print("v_info =" + v_info)
with open(Path_alertinfo) as alertinfo:
    a_info = alertinfo.read().replace("\n", "")
print("a_info =" + a_info)

def connect_database_common():
    """
    共通データベースにアクセスする処理
    """
    global common_pj

    common_pj = mysql.connector.connect(
        user=COMMON_DB_USER, password=COMMON_DB_PASS, host=COMMON_DB_HOST, database=COMMON_DB_NAME)

def close_con_connect(con_name, cur_name):
    """
    引数で受け取った、データベース接続情報と、カーソルをCloseする処理
    """
    con_name.close()
    cur_name.close()

def get_line_token():
    """
    共通データベースからLINEトークンを取得する処理
    """
    # グローバル変数に代入するために宣言
    global line_token

    # データベース接続処理
    connect_database_common()

    # 共通データベースのカーソルを取得
    line_cur = common_pj.cursor()
    line_cur.execute(
        "SELECT * FROM m_common_token WHERE project_name='" + pj_name + "'")

    for line_row in line_cur.fetchall():
        # line_id = line_row[0]
        line_token = line_row[1]

    # 後処理としてクローズ処理を実行する
    close_con_connect(common_pj, line_cur)

# -----< LINE notify Function definition-Start >-----
def LINE_notify(str_message):
    """
    LINE Notifyの接続処理
    """

    url = "https://notify-api.line.me/api/notify"

    #LINEトークン取得処理
    get_line_token()

    headers = {"Authorization": "Bearer " + line_token}
    print(line_message)
    payload = {"message":  line_message}

    r = requests.post(url, headers=headers, params=payload)

# -----< LINE notify Function definition-End >-----


# ---< 2020/04/15 update-start >--
if format(FILE_TIMESTAMP) > format(BEFORE_30min):  # 計測が停止しているか判定
    # 最新の測定値なのでしきい値のチェックを行う
    line_message = line_message + "\n電圧【 " + v_info + "V 】"
    # << Voltage check >>
    if (float(v_info) >= b_low_limit) and (float(v_info) <= b_high_limit):
        if (a_info == "ABNORMAL"):
            # alertinfo.ini update("ABNORMAL" --> "CLEAR_1TIME")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("CLEAR_1TIME")
        elif (a_info == "CLEAR_1TIME"):
            # alertinfo.ini update("CLEAR_1TIME" --> "CLEAR_2TIME")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("CLEAR_2TIME")
        elif (a_info == "CLEAR_2TIME"):
            # LINE alert
            line_message = line_message + "電圧が回復しました。"
            LINE_notify(line_message)  # LINE to message send
            # alertinfo.ini update("CLEAR_2TIME" --> "NORMAL")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("NORMAL")
        elif (a_info == "UNAVAILABLE"):
            # LINE alert
            line_message = line_message + "計測が再開されました。"
            LINE_notify(line_message)  # LINE to message send
            # alertinfo.ini update("UNAVAILABLE" --> "NORMAL")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("NORMAL")
        else:
            pass
    # << voltage abnormal >>
    elif (float(v_info) < b_low_limit):
        if (a_info == "NORMAL" or a_info == "UNAVAILABLE"):
            # LINE alert
            line_message = line_message + "電圧が低下しました。"
            LINE_notify(line_message)  # LINE to message send
        else:
            pass
        # alertinfo.ini update("******" --> "ABNORMAL")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("ABNORMAL")
    elif (float(v_info) > b_high_limit):
        if (a_info == "NORMAL" or a_info == "UNAVAILABLE"):
            # LINE alert
            line_message = line_message + "電圧が上昇しました。"
            LINE_notify(line_message)  # LINE to message send
        else:
            pass
        # alertinfo.ini update("******" --> "ABNORMAL")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("ABNORMAL")
    else:
        print("前回の状態を継続中")
else:  # 計測が停止している可能性あり
    if a_info != "UNAVAILABLE":
        # LINE alert
        line_message = line_message + "\n計測が停止しています。"
        LINE_notify(line_message)  # LINE to message send
        # alertinfo.ini update("******" --> "UNAVAILABLE")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("UNAVAILABLE")


# ---< 2020/04/15 update-end >--
