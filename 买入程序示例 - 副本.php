#encoding=utf-8
#python�ı�������

import urllib2
import json
import random


# url_limit_stock = 'http://www.haizhilicai.com/stock_limit/get_stock_limit/60'

#��ȡ��Ʊ��ͣ�б�
url_limit_stock = 'http://192.168.0.136/test_python/get_sz_stocks'
request_limit_stock = urllib2.Request(url_limit_stock)
response_limit_stock = urllib2.urlopen(request_limit_stock)
data_limit_stock = response_limit_stock.read().decode('utf-8')
result_limit_stock = json.loads(data_limit_stock)


#���������Nֻ�����еĹ�Ʊ
def produce_sz_stock(n,list_sz_stocks):
    result_list = []
    len_sz_stock = len(list_sz_stocks)
    for i in range(0,n):
        #�������nֻ��Ʊ
        temp = random.randint(0, len_sz_stock)
        result_list.append(list_sz_stocks[temp])
    return result_list

# �������
def stock_buy_random(user_id,list_stock):
    print user_id
    for item in list_stock:
        url_str = 'http://192.168.0.136/test_python/' + 'fun_buy_stock/' + user_id + '/' + item
        print url_str
        request = urllib2.Request(url_str)
        response = urllib2.urlopen(request)
        print response.read().decode('utf-8')


# �û�XXX���������ĳ��ֻ��Ʊ
user_id = '888000058$'
random_buy_num  = 3

random_stocks = produce_sz_stock(random_buy_num,result_limit_stock)
stock_buy_random(user_id,random_stocks)
















#
# str = ""
#
# user_id = '888000058$'
#
# stock_id = '000001'
#
# str = 'http://192.168.0.136/test_python/'+'fun_sell_stock/'+user_id+'/'+stock_id












