SHELL=/bin/sh
BENCHMARK=ep
BENCHMARKU=EP

include ../config/make.def

HTTPSRCS = $(filter-out makejob.cpp,$(wildcard *.cpp))
OBJS = ep.o $(patsubst %.cpp,%.o,$(HTTPSRCS)) ${COMMON}/print_results.o ${COMMON}/${RAND}.o ${COMMON}/timers.o

include ../sys/make.common

EPPROGRAM = $(BINDIR)/$(BENCHMARK).$(CLASS).x

${PROGRAM}: config ${OBJS}
	${FLINK} ${FLINKFLAGS} -no-pie -o ${EPPROGRAM} ${OBJS} ${FMPI_LIB} -fopenmp -lstdc++


ep.o:		ep.f  mpinpb.h npbparams.h
	${FCOMPILE} ep.f -cpp -DMYLOG

.cpp.o:
	g++ -std=c++11 $< -c -DMYFORTRAN -DMYLOG

clean:
	- rm -f *.o *~ 
	- rm -f npbparams.h core



