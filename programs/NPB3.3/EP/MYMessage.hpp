#ifndef MYMESSAGE_HPP
#define MYMESSAGE_HPP

#include "MYSocket.hpp"
#include "MYTimer.hpp"

void show_buf(const char *buf,int size,int id);
void normal_header(MYSocket* sock,const char* path, int id, int body_len);
void file_header(MYSocket *sock,const char* path, int id, int body_len, const char* filename);
int text_end_len(const char* filename);
void text_end(MYSocket *sock);
void recv_response_header(const MYSocket &sock, MYTimer &timer, char* recv_buf, int buf_len, int id);
int get_content_length(char* recv_buf, int id);
void recv_body(const MYSocket &sock, char* recv_buf, int buf_len, int id, int content_len, int received);
bool recv_message_ok(const MYSocket &sock, MYTimer &timer, int id, int buf_len);
void recv_message_not_no(const MYSocket &sock, MYTimer &timer, int id, int buf_len);

#endif
