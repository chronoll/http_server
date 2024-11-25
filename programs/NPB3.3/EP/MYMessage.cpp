#include<cstdio>
#include<cstdlib>
#include<cstring>

// MYSocket.cpp
#include "MYSocket.hpp"
#include "MYTimer.hpp"

#include "MYLog.h"

// recv関数後のbufの中身を見るために使用
void show_buf(const char *buf,int size,int id){
	logprintf("ID_%d_buf:",id);
	for(int i=0;i<size;i++){
		if(buf[i]=='\n')
			logprintf("\\n");
		else if(buf[i]=='\r')
			logprintf("\\r");
		else if(buf[i]=='\0')
			logprintf("#");
		else
			logprintf("%c",buf[i]);
	}
	logprintf("\n");
}

void normal_header(MYSocket* sock,const char* path, int id, int body_len){
	char* text=(char*)malloc(sizeof(char)*512);
	//http header [POST] or [GET]
	sprintf(text, "POST %s?ID_%d HTTP/1.1\r\n", path,id);
	sock->mysend(text, strlen(text));

	//Set Host
	sprintf(text, "Host: %s:%d\r\n", sock->get_host(), sock->get_port());
	sock->mysend(text, strlen(text));

	//POSTで変数を送信する場合、以下のコンテンツタイプを指定する
	sprintf(text, "Content-Type: application/x-www-form-urlencoded\r\n");
	sock->mysend(text, strlen(text));

	//コンテンツの長さ（バイト）を指定する
	sprintf(text, "Content-Length: %d\r\n", body_len);
	sock->mysend(text, strlen(text));

	sprintf(text, "Connection: Close\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "\r\n");
	sock->mysend(text, strlen(text));
}

void file_header(MYSocket *sock,const char* path, int id, int body_len, const char* filename){
	char* text=(char*)malloc(sizeof(char)*512);
	sprintf(text, "POST %s?ID_%d HTTP/1.1\r\n", path,id);
	sock->mysend(text, strlen(text));

	sprintf(text, "Host: %s:%d\r\n", sock->get_host(), sock->get_port());
	sock->mysend(text, strlen(text));

	sprintf(text, "Content-Length: %d\r\n", body_len);
	sock->mysend(text, strlen(text));

	sprintf(text, "Connection: Close\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "Content-Type: multipart/form-data; boundary=xYzZY\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "--xYzZY\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "Content-Disposition: form-data; name=\"file\"; filename=%s\r\n", filename);
	sock->mysend(text, strlen(text));

	sprintf(text, "Content-Type: text/plain\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "\r\n");
	sock->mysend(text, strlen(text));
}

int text_end_len(const char* filename){
	char* text=(char*)malloc(sizeof(char)*512);
	int len=0;
	sprintf(text, "--xYzZY\r\n");
	len+=strlen(text);

	sprintf(text, "Content-Disposition: form-data; name=\"file\"; filename=%s\r\n",filename);
	len+=strlen(text);

	sprintf(text, "Content-Type: text/plain\r\n");
	len+=strlen(text);

	sprintf(text, "\r\n");
	len+=strlen(text);

	sprintf(text, "\r\n");
	len+=strlen(text);

	sprintf(text, "--xYzZY--\r\n");
	len+=strlen(text);

	sprintf(text, "\r\n");
	len+=strlen(text);
	return len;
}
void text_end(MYSocket *sock){
	char* text=(char*)malloc(sizeof(char)*512);
	sprintf(text, "\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "--xYzZY--\r\n");
	sock->mysend(text, strlen(text));

	sprintf(text, "\r\n");
	sock->mysend(text, strlen(text));
}

void recv_response_header(const MYSocket &sock, MYTimer &timer, char* recv_buf, int buf_len, int id){
	int total_len=0;
	int recv_max=buf_len-1;
	bool is_received=false;
	do{
		int recv_len = sock.myrecv(&recv_buf[total_len], recv_max-total_len);
		if(is_received==false){
			timer.date(); // 受信時刻
			is_received=true;
		}
		show_buf(recv_buf,buf_len,id);
		total_len+=recv_len;
		recv_buf[total_len] = '\0';
		if(total_len>buf_len){
			logprintf("error: HTTP header is too big >%d id=%d",total_len,id);
			exit(1);
		}
	}while(strstr(recv_buf, "\r\n\r\n")==NULL);
}

int get_content_length(char* recv_buf, int id){
	char *tmp;
	if((tmp=strstr(recv_buf,"Content-Length: "))==NULL){
		logprintf("error: Content-length is not found id=%d\n",id);
		exit(1);
	}
	int content_len;
	sscanf(tmp,"Content-Length: %d",&content_len);
	return content_len;
}

void recv_body(const MYSocket &sock, char* recv_buf, int buf_len, int id, int content_len, int received){
	int offset=strlen(recv_buf); // recv_bufにすでにある文字列
	int total_len=offset;
	while (received<content_len){
		int recv_len = sock.myrecv(&recv_buf[total_len], buf_len-total_len);
		total_len+=recv_len;
		recv_buf[total_len]='\0';
		if(total_len>buf_len){
			logprintf("error: HTTP body is too big >%d id=%d",total_len,id);
			exit(1);
		}
	}
}

// FLAG用
bool recv_message_ok(const MYSocket &sock, MYTimer &timer, int id, int buf_len){
	timer.start(); // 受信開始
	char recv_buf[buf_len+1];
	memset(recv_buf, 0, sizeof(recv_buf));

	// ヘッダーの受信
	recv_response_header(sock,timer,recv_buf,sizeof(recv_buf),id);

	// Content-Lengthを取得
	int content_len=get_content_length(recv_buf, id);

	// ボディの受信
	strcpy(recv_buf,strstr(recv_buf,"\r\n\r\n")+4);
	recv_body(sock, recv_buf, sizeof(recv_buf), id, content_len, strlen(recv_buf));

	timer.stop(); // 受信終了
	if (!strncmp(recv_buf, "OK\n",3)){
		logprintf("OK\n");
		return true;
	}else{
		logprintf("%s\n",recv_buf);
		return false;
	}
}
// ID, DATA用
void recv_message_not_no(const MYSocket &sock, MYTimer &timer, int id, int buf_len){
	if(!recv_message_ok(sock, timer, id, buf_len)){
		logprintf("error: response is not ok id=%d\n",id);
		exit(1);
	}
}
// recDATA用 今の所使わない
bool recv_data(const MYSocket &sock, MYTimer &timer, int id, int buf_len){
	timer.start(); // 受信開始
	char recv_buf[buf_len+2]; // \n(門番)と\0用に+2
	memset(recv_buf, 0, sizeof(recv_buf)); // すべて0に初期化
	// ヘッダの受信
	recv_response_header(sock,timer,recv_buf,buf_len,id);
	// Content-lengthの取得
	int content_len=get_content_length(recv_buf, id);
	// ボディ: OKかどうか
	char* body_start=strstr(recv_buf,"\r\n\r\n")+4;
	if(strncmp(body_start,"OK\n",3)!=0){
		logprintf("%s\n",body_start);
		return false;
	}
	// ヘッダーとOK\nを削除
	strcpy(recv_buf,body_start+3);
	int offset=strlen(recv_buf); // ヘッダーと同時に受信したボディの長さ
	recv_buf[offset]='\n';
	recv_buf[offset+1]='\0';

	int total_len=offset+3; // 受信したボディの総長
	bool separated=false; // 値が分断されているか
	char buf_read[25]; //値1つ分の文字列
	int buf_read_p=0; // buf_readの読み取り位置
	int recv_buf_p=0; //recv_bufの読み取り位置
	int read_size;
	while (1){
		// ボディの受信
		if(total_len<content_len){
			read_size = sock.myrecv(&recv_buf[offset], buf_len-offset);
			recv_buf[read_size+offset]='\n'; // 門番
			total_len+=read_size;
		}else if(strlen(recv_buf)==0) break;
		offset=0; // 以後0
		show_buf(recv_buf,sizeof(recv_buf),id);
		recv_buf_p=0;
		// ボディの文字列を数値に変換
		while(1){
			// 値が分断されていなければbuf_readを初期化
			if(separated==false){
				memset(buf_read, 0, sizeof(buf_read));
				buf_read_p=0;
			}
			// bufからbuf_readに値1つ分の文字列を読み込む
			while(recv_buf[recv_buf_p] != '\n'){
				buf_read[buf_read_p++] = recv_buf[recv_buf_p++];
			}
			recv_buf_p++;
			// \nの次が\0ならば、値が分断されているので，もう一度recv
			if(recv_buf[recv_buf_p]=='\0'){
				separated=true;
				break;
			// 分断されていなければ，数値に変換
			}else{
				separated=false;
				// ここに1つの値を適当な型に変換し配列に代入する動作
				// CODE;
				// \nの次が\nならbufの値をすべて数値に変換した
				if(recv_buf[recv_buf_p]=='\n') break;
			}
		}
		memset(recv_buf, 0, sizeof(recv_buf));
	}
	return true;
}