#encoding=utf-8
#python�ı�������

import urllib2
import json
import random



#��ȡĳ�û���ǰ�ֲֹ�Ʊ����-��result_hold_stock
url_hold_stock = 'http://192.168.0.136/test_python/get_hold_stock/888000058$'
request_hold_stock = urllib2.Request(url_hold_stock)
response_hold_stock = urllib2.urlopen(request_hold_stock)
data_hold_stock = response_hold_stock.read().decode('utf-8')
result_hold_stock = json.loads(data_hold_stock)


#��������
def stock_sell(user_id,list_stock):
    print user_id
    for item in list_stock:
        url_str = 'http://192.168.0.136/test_python/' + 'fun_sell_stock/' + user_id + '/' + item
        print url_str
        request = urllib2.Request(url_str)
        response = urllib2.urlopen(request)

        print response.read().decode('utf-8')



# �ڶ�������гֲֵĹ�Ʊȫ������
user_id = '888000058$'
stock_sell(user_id,result_hold_stock)













#
# str = ""
#
# user_id = '888000058$'
#
# stock_id = '000001'
#
# str = 'http://192.168.0.136/test_python/'+'fun_sell_stock/'+user_id+'/'+stock_id












