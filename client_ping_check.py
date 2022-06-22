#!/usr/bin/ python
# -*- coding: utf-8 -*-
"""
モニタリングシステムRaspberryPiとの通信ができているか確認する処理
"""
import subprocess
import datetime
import os
import time
import ssl
from smtplib import SMTP, SMTP_SSL
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.utils import formatdate

# ------- global ------- #
now = None
ping_address = None
client_name = None
failed_client = None
from_email = None
to_email = None
subject = None
message = None
# ------- global ------- #


def server_ping():
    """
    クラウドサーバへのネットワーク接続確認処理
    """
    global now, ping_address, client_name, failed_client
    result_total = []
    failed_client = ""
    i = 0
    for ping_target in ping_address:
        # 20回打って戻りを確認する
        for j in range(20):
            time.sleep(1)
            try:
                ping_check = os.system("ping " + ping_target + " -c 1")
                # 通った場合のリターンコードは0　そのまま配列に加える
                result_total.append(ping_check)
            except:
                # 失敗もしくは何らかのエラーが起きた場合は1を加える
                print(ping_target + " error.")
                result_total.append(1)

        # 一度でも通っていればOK、リターンコード0が一つもないときにエラーとする
        if result_total.count(0) == 0:
            print(now + client_name[i] + " network NG.\n")
            failed_client = failed_client + client_name[i] + "\n"
        else:
            print(now + client_name[i] + " network OK.")
            pass
        i += 1


def server_ping_bk():
    """
    クラウドサーバへのネットワーク接続確認処理
    """
    global now, ping_address, client_name, failed_client
    result_total = []
    failed_client = ""
    i = 0
    for ping_target in ping_address:
        # 20回打って戻りを確認する
        for j in range(20):
            time.sleep(1)
            try:
                print("ping start. - " + ping_target)
                ping_check = subprocess.run(["ping", ping_target, "-c", "1"],
                                            encoding='utf-8',
                                            stdin=subprocess.PIPE,
                                            stdout=subprocess.PIPE)
                print("1")
                # 通った場合のリターンコードは0
                ping_check = ping_check.returncode
                print("2")
                result_total.append(ping_check)
                print("#ping end.")
            except:
                # 失敗もしくは何らかのエラーが起きた場合は1
                print(ping_target + " error.")
                result_total.append(1)

        # 一度でも通っていればOK、すべてダメだった場合は再起動フラグ
        if result_total.count(0) == 0:
            print(now + client_name[i] + " network NG.\n")
            failed_client = failed_client + client_name[i] + "\n"
        else:
            print(now + client_name[i] + " network OK.")
            pass
        i += 1



def createMailMessageMIME(froms, to, message, subject, filepath=None, filename=""):
    # MIMETextを作成
    msg = MIMEMultipart()
    msg['Subject'] = subject
    msg['From'] = froms
    msg['To'] =  to
    msg.attach(MIMEText(message, 'plain', 'utf-8'))
    return msg


def send_email(msg):
    account = "alart@moni-sys.com"
    password = "8VsW&kuo"

    host = 'smtp20.gmoserver.jp'
    port = 465

    # サーバを指定する
    #server = SMTP(host, port)
    context = ssl.create_default_context()
    server = SMTP_SSL(host, port, context=context)

    # 確認
    if server.has_extn('STARTTLS'):
        # ehloは内部で勝手に実行してくれるが、あえで手動で実行したい場合は明示的にechoすることができる
        server.ehlo()
        server.starttls()
        server.ehlo()
    # ログイン処理
    server.login(account, password)

    # メールを送信する
    server.send_message(msg)

    # 閉じる
    server.quit()


def mailtohoro():
    # MIME形式の作成
    mime = createMailMessageMIME(from_email, to_email, message, subject)
    # メールの送信
    try:
        send_email(mime)
    except:
        print("mail NG.")
    else:
        print("mail OK.")




def main():
    global now, ping_address, client_name, from_email, to_email, subject, message, failed_client
    now = datetime.datetime.now().strftime("[%Y/%m/%d %H:%M:00] - ")
    ping_address = ["210.156.171.241", "210.156.160.22"]
    client_name = ["marukin", "ksfoods"]

    # 通信確認
    server_ping()
    if failed_client == "":
        pass
    else:
        # メール送信設定開始
        # メールの送り主
        from_email = "Moni-sys_Server"

        # メール送信先
        to_email = "monisys.ci.alert@gmail.com"
        errortime = datetime.datetime.now().strftime('[%Y/%m/%d %H:%M:%S]')
        subject = "Client_ping_Error."
        message = errortime + "\n" + failed_client + "\nplease system check."
        mailtohoro()


if __name__ == '__main__':
    main()

