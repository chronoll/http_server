#ifndef MYMPI_H
#define MYMPI_H

void HTTP_Allreduce(int YOUR_ID, int allreduce_ID, int numprocs, void *send_hiki_DATA_P, void *recv_hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE, const char *op, int isolation);
void HTTP_Alltoall(int YOUR_ID, int alltoall_ID, int numprocs, void *send_hiki_DATA_P, int send_DATA_LEN, const char *send_DATA_TYPE, void *recv_hiki_DATA_P, int recv_DATA_LEN, const char *recv_DATA_TYPE,int isolation);
void HTTP_Alltoallv(int YOUR_ID, int alltoallv_ID, int numprocs, void *send_hiki_DATA_P, int *send_DATA_LEN, int *send_displs, const char *send_DATA_TYPE, void *recv_hiki_DATA_P, int *recv_DATA_LEN, int *recv_displs, const char *recv_DATA_TYPE, int isolation);
void HTTP_Bcast(int YOUR_ID, int bcast_ID, int numprocs, void *hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE, int isolation);
void HTTP_Gather(int YOUR_ID, int gather_ID, int numprocs, void *send_hiki_DATA_P, int send_DATA_LEN, const char *send_DATA_TYPE, void *recv_hiki_DATA_P, int recv_DATA_LEN, const char *recv_DATA_TYPE, int isolation);
void HTTP_Recv(int YOUR_ID, int RECEIVE_FROM_ID, int TAG, void *hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE);
void HTTP_Reduce(int YOUR_ID, int reduce_ID, int numprocs, void *send_hiki_DATA_P, void *recv_hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE, const char *op, int isolation);
void HTTP_Send(int YOUR_ID, int SEND_TO_ID, int TAG, void *hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE);

#endif
