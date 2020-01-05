import socket
import ssl
from http.server import BaseHTTPRequestHandler,HTTPServer

def send_request(myself, req):
  if(req == '/favicon.ico'):
    print('favicon.ico')
    return

  station = req[4:]
  hostname = 'tgftp.nws.noaa.gov'
  protocol = 'GET https://'
  page = protocol + hostname + station + ' HTTP/1.1\r\nHost: ' +hostname + '\r\nConnection: close\r\nContent-Type: text/plain\r\n\r\n'
  context = ssl.create_default_context()

  print(page)

  with socket.create_connection((hostname, 443)) as sock:
      with context.wrap_socket(sock, server_hostname=hostname) as ssock:
        ssock.sendall(page.encode())
        while(1):
            receivedMsg = ssock.recv(1024)
#            print(receivedMsg)
            if(len(receivedMsg) == 0):
                ssock.close()
                break
            myself.wfile.write(receivedMsg)

class RequestHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        result = send_request(self,self.path)

#----------------------------------------------------------------------

if __name__ == '__main__':
    serverAddress = ('', 8083)
    server = HTTPServer(serverAddress, RequestHandler)
    server.serve_forever()

