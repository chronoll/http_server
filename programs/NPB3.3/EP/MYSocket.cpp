#include<cstdio>
#include<cstdlib>
#include<cstring>
#include<sys/socket.h>
#include<sys/types.h>
#include<arpa/inet.h>
#include<netinet/in.h>
#include<unistd.h>
#include<cerrno>

#include "MYSocket.hpp"
#include "MYLog.h"

MYSocket::MYSocket(int port,const char* host){
	this->port=port;
	sprintf(httphost,"%s",host);
	memset(&dstAddr, 0, sizeof(dstAddr));
	dstAddr.sin_port = htons(port);
	dstAddr.sin_family = AF_INET;
	dstAddr.sin_addr.s_addr = inet_addr(host);
}
void MYSocket::mysock(){
	errno=0;
	this->dstSocket = socket(AF_INET, SOCK_STREAM, 0);
	int tmp_err=errno;
	if (this->dstSocket < 0){
		logprintf("error: socket() %s\n",strerror(tmp_err));
		exit(1);
	}
}
void MYSocket::myconn(){
	errno=0;
	int result = connect(this->dstSocket, (struct sockaddr *)&(this->dstAddr), sizeof(this->dstAddr));
	int tmp_err=errno;
	if (result < 0){
		logprintf("error: connect() %s\n",strerror(tmp_err));
		exit(1);
	}
}
int MYSocket::myrecv(char* buf, int len) const{
	errno=0;
	int result=recv(this->dstSocket, buf, len, 0);
	buf[result]='\0';
	int tmp_err=errno;
	if(result<0){
		logprintf("error: recv() %s\n",strerror(tmp_err));
		exit(1);
	}
	return result;
}
int MYSocket::mysend(const char* buf, int len) const{
	errno=0;
	int result=send(this->dstSocket, buf, len, 0);
	int tmp_err=errno;
	if(result<0){
		logprintf("error: send() %s\n",strerror(tmp_err));
		exit(1);
	}
	return result;
}