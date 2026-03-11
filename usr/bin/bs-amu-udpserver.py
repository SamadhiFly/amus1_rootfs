#!/usr/bin/env python
# coding=utf-8



#=============================================================================
# Import packages
#=============================================================================
import socket
import time
import json

#=============================================================================
# Global Variables
#=============================================================================
SERVER_SOCKET = 0
SERVER_PORT = 6789
IPADDR_FILE = "/tmp/udp_server_ip"

#=============================================================================
# create socket
#=============================================================================
def create_server_socket():
    global SERVER_SOCKET
    address = ('0.0.0.0', SERVER_PORT)
    SERVER_SOCKET = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    SERVER_SOCKET.bind(address)

#=============================================================================
# refresh file
#=============================================================================
def refresh_file(ip):
    fo = open(IPADDR_FILE, "w+")
    fo.write(ip)
    fo.close()

#=============================================================================
# Main code start
#=============================================================================
if __name__=='__main__':
    '''create udp server'''
    create_server_socket()

    '''loop '''
    while True:
        data, addr = SERVER_SOCKET.recvfrom(2048)
        if not data:
            time.sleep(3)        
            continue
        print "received:", data, "from", addr

        try:
            json_data = json.loads(data)
            box_ip = json_data['ip']
            print box_ip
        except Exception,e:
            time.sleep(3)        
            continue

        refresh_file(box_ip)
        time.sleep(3)        
        
    SERVER_SOCKET.close()
