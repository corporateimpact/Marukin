#!/usr/bin/env python abort_voltage LINE alert 
#coding=utf-8
#----- 電圧異常時のＬＩＮＥアラート -----
import requests
import os
import time , datetime

#<< file path >>
#<マルキンサーバ>
Path_batteryinfo = "/home/upload/infos/battery/batteryinfo.ini"
Path_alertinfo = "/home/upload/infos/battery/alertinfo.ini"

#<< Battery Boundary >>
b_high_limit = 15.0
b_low_limit = 11.5

#<< 30分前の時刻を取得 >>
#BEFORE_30min = format(datetime.datetime.fromtimestamp(time.time() - 1800))[:19]
BEFORE_30min = format(datetime.datetime.fromtimestamp(time.time() - 86400))[:19]
print("BEFORE_30min =" + BEFORE_30min)

#<< Text-File Open >>
FILE_TIMESTAMP = datetime.datetime.fromtimestamp(os.path.getmtime(Path_batteryinfo))
print("FILE_TIMESTAMP =" + format(FILE_TIMESTAMP))

with open(Path_batteryinfo) as voltinfo:
	v_info = voltinfo.read().replace("\n","")
print("v_info =" + v_info)
with open(Path_alertinfo) as alertinfo:
	a_info = alertinfo.read().replace("\n","")
print("a_info =" + a_info)

line_message = "<< マルキン アラート >>"


#-----< LINE notify Function definition-Start >-----
def LINE_notify(line_message):
    url = "https://notify-api.line.me/api/notify"
#    token = #Here ACCESS-TOKEN input
    token = "C7sEqmhEl6z1vKZxf8a7abS6jXynUUPuKGX26YrmbEh" #<-GINZAKE pj
#    token = "ObFoG8pLNgIGpm04j7t0abp9wKMAJAuHHp08VFihIOb" #<-TEST鈴木宛用トークン
    headers = {"Authorization" : "Bearer "+ token}
    print (line_message)
    payload = {"message" :  line_message}

    r = requests.post(url ,headers = headers ,params=payload)

#-----< LINE notify Function definition-End >-----


#---< 2020/04/15 update-start >--
if format(FILE_TIMESTAMP) > format(BEFORE_30min): # 計測が停止しているか判定
    # 最新の測定値なのでしきい値のチェックを行う
    line_message = line_message + "\n電圧【 " + v_info + "V 】"
    #<< Voltage check >>
    if (float(v_info) >= b_low_limit) and (float(v_info) <= b_high_limit):
        if (a_info == "ABNORMAL"):
            #alertinfo.ini update("ABNORMAL" --> "CLEAR_1TIME")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("CLEAR_1TIME")
        elif (a_info == "CLEAR_1TIME"):
            #alertinfo.ini update("CLEAR_1TIME" --> "CLEAR_2TIME")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("CLEAR_2TIME")
        elif (a_info == "CLEAR_2TIME"):
            #LINE alert
            line_message = line_message + "電圧が回復しました。"
            LINE_notify(line_message)	#LINE to message send
            #alertinfo.ini update("CLEAR_2TIME" --> "NORMAL")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("NORMAL")
        elif (a_info == "UNAVAILABLE"):
            #LINE alert
            line_message = line_message + "計測が再開されました。"
            LINE_notify(line_message)	#LINE to message send
            #alertinfo.ini update("UNAVAILABLE" --> "NORMAL")
            with open(Path_alertinfo, mode="w") as alertinfo_w:
                alertinfo_w.write("NORMAL")
        else:
            pass
    #<< voltage abnormal >>
    elif (float(v_info) < b_low_limit):
        if (a_info == "NORMAL" or a_info == "UNAVAILABLE"):
            #LINE alert
            line_message = line_message + "電圧が低下しました。"
            LINE_notify(line_message)	#LINE to message send
        else:
            pass
        #alertinfo.ini update("******" --> "ABNORMAL")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("ABNORMAL")
    elif (float(v_info) > b_high_limit):
        if (a_info == "NORMAL" or a_info == "UNAVAILABLE"):
            #LINE alert
            line_message = line_message + "電圧が上昇しました。"
            LINE_notify(line_message)	#LINE to message send
        else:
            pass
        #alertinfo.ini update("******" --> "ABNORMAL")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("ABNORMAL")
    else:
        print("前回の状態を継続中")
else: # 計測が停止している可能性あり
    if a_info != "UNAVAILABLE" :
        #LINE alert
        line_message = line_message + "\n計測が停止しています。"
        LINE_notify(line_message)	#LINE to message send
        #alertinfo.ini update("******" --> "UNAVAILABLE")
        with open(Path_alertinfo, mode="w") as alertinfo_w:
            alertinfo_w.write("UNAVAILABLE")


#---< 2020/04/15 update-end >--
