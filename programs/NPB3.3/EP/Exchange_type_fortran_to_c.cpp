#include <stdio.h>
#include "ftype.h"

// MPI型(fortran)をMPI型(c)に変換する
// 未対応
// MPI_COMPLEX, MPI_DOUBLE_COMPLEX (構造体のため)
// MPI_INTEGER1 (signd charのため)
// MPI_REAL16 (long doubleのため)
const char *ftype_to_ctype(int type){
	char* send_type_c;
	if(type==MPI_INTEGER || type==MPI_LOGICAL || type==MPI_INTEGER4)
		return "MPI_INT";
	else if(type==MPI_INTEGER2)
		return "MPI_SHORT";
	else if(type==MPI_INTEGER8)
		return "MPI_LONG";
	else if(type==MPI_REAL || type==MPI_REAL4)
		return "MPI_FLOAT";
	else if(type==MPI_DOUBLE_PRECISION || type==MPI_REAL8)
		return "MPI_DOUBLE";
	else if(type==MPI_CHARACTER || type==MPI_BYTE)
		return "MPI_CHAR";
	else
		return NULL;
}
const char *fop_to_cop(int op){
	if(op==MPI_MAX) return "MPI_MAX";
	else if(op==MPI_MIN) return "MPI_MIN";
	else if(op==MPI_SUM) return "MPI_SUM";
	else return NULL;
}