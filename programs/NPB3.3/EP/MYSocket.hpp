#ifndef MYSocket_HPP
#define MYSocket_HPP

#include<sys/socket.h>
#include<sys/types.h>
#include<arpa/inet.h>
#include<netinet/in.h>
#include<unistd.h>

class MYSocket{
	private:
		unsigned short port;
		char httphost[20];
		int dstSocket;
		struct sockaddr_in dstAddr;
	public:
		MYSocket(int port,const char* host);
		void mysock();
		void myconn();
		int myrecv(char* buf, int len) const;
		int mysend(const char* buf, int len) const;
		void myclose() const{
			close(this->dstSocket);
		}
		const char* get_host() const{
			return this->httphost;
		}
		unsigned short get_port() const{
			return this->port;
		}
};

#endif
