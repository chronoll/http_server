!-------------------------------------------------------------------------!
!                                                                         !
!        N  A  S     P A R A L L E L     B E N C H M A R K S  3.3         !
!                                                                         !
!                                   E P                                   !
!                                                                         !
!-------------------------------------------------------------------------!
!                                                                         !
!    This benchmark is part of the NAS Parallel Benchmark 3.3 suite.      !
!    It is described in NAS Technical Reports 95-020 and 02-007           !
!                                                                         !
!    Permission to use, copy, distribute and modify this software         !
!    for any purpose with or without fee is hereby granted.  We           !
!    request, however, that all derived work reference the NAS            !
!    Parallel Benchmarks 3.3. This software is provided "as is"           !
!    without express or implied warranty.                                 !
!                                                                         !
!    Information on NPB 3.3, including the technical report, the          !
!    original specifications, source code, results and information        !
!    on how to submit new results, is available at:                       !
!                                                                         !
!           http://www.nas.nasa.gov/Software/NPB/                         !
!                                                                         !
!    Send comments or suggestions to  npb@nas.nasa.gov                    !
!                                                                         !
!          NAS Parallel Benchmarks Group                                  !
!          NASA Ames Research Center                                      !
!          Mail Stop: T27A-1                                              !
!          Moffett Field, CA   94035-1000                                 !
!                                                                         !
!          E-mail:  npb@nas.nasa.gov                                      !
!          Fax:     (650) 604-3957                                        !
!                                                                         !
!-------------------------------------------------------------------------!


c---------------------------------------------------------------------
c
c Authors: P. O. Frederickson 
c          D. H. Bailey
c          A. C. Woo
c          R. F. Van der Wijngaart
c---------------------------------------------------------------------

c---------------------------------------------------------------------
      program EMBAR
c---------------------------------------------------------------------
C
c   This is the MPI version of the APP Benchmark 1,
c   the "embarassingly parallel" benchmark.
c
c
c   M is the Log_2 of the number of complex pairs of uniform (0, 1) random
c   numbers.  MK is the Log_2 of the size of each batch of uniform random
c   numbers.  MK can be set for convenience on a given system, since it does
c   not affect the results.

      use,intrinsic :: iso_fortran_env
      !use omp_lib

      implicit none

      include 'npbparams.h'
      include 'mpinpb.h'

      integer          all_num,dp1_type,dp2_type,
     >                 type_double,type_sum,type_max,type_min,
     >                 re_num,bc_num,ba_num,type_int
c      character(10)  :: type_double
c      character(7)   :: type_max
c      character(7)   :: type_sum
c      character(7)   :: type_min

      real(real64)   :: time_begin_s,time_end_s
      double precision Mops, epsilon, a, s, t1, t2, t3, t4, x, x1, 
     >                 x2, q, sx, sy, tm, an, tt, gc, dum(3),
     >                 timer_read
      double precision sx_verify_value, sy_verify_value, sx_err, sy_err
      integer          mk, mm, nn, nk, nq, np, ierr, node, no_nodes, 
     >                 i, ik, kk, l, k, nit, ierrcode, no_large_nodes,
     >                 np_add, k_offset, j
      logical          verified, timers_enabled
      external         randlc, timer_read
      double precision randlc, qq
      character*15     size

c      コマンドライン引数で受け取る
      character(len=10) :: arg1, arg2

      integer :: count_start, count_end, count_rate

      double precision :: sx_array(1)
      double precision :: sy_array(1)

      integer          fstatus
      integer          t_total, t_gpairs, t_randn, t_rcomm, t_last
      parameter (t_total=1, t_gpairs=2, t_randn=3, t_rcomm=4, t_last=4)
      double precision tsum(t_last+2), t1m(t_last+2),
     >                 tming(t_last+2), tmaxg(t_last+2)
      character        t_recs(t_last+2)*8

      parameter (mk = 16, mm = m - mk, nn = 2 ** mm,
     >           nk = 2 ** mk, nq = 10, epsilon=1.d-8,
     >           a = 1220703125.d0, s = 271828183.d0)

      common/storage/ x(2*nk), q(0:nq-1), qq(10000)
      data             dum /1.d0, 1.d0, 1.d0/

      data t_recs/'total', 'gpairs', 'randn', 'rcomm',
     >            ' totcomp', ' totcomm'/

c      コマンドライン引数を取得
      call get_command_argument(1, arg1)
      call get_command_argument(2, arg2)

c      引数を整数に変換
      read(arg1, *) node
      read(arg2, *) no_nodes

c      引数の確認
      write(*, *) "Node ID:", node
      write(*, *) "Total Nodes:", no_nodes

      sx_array(1) = sx
      sy_array(1) = sy

c      call mpi_init(ierr)
c     call mpi_comm_rank(MPI_COMM_WORLD,node,ierr)
c     call mpi_comm_size(MPI_COMM_WORLD,no_nodes,ierr)

      call log_fopen(node)

      root = 0
      all_num = 0
      re_num = 0
      bc_num = 0
      ba_num = 0
c      dp1_type = MPI_DOUBLE_PRECISION
      dp1_type = 17
c      dp2_type = MPI_REAL
      dp2_type = 13
      type_sum = 1
      type_max = 2
      type_min = 3
      type_double = 4
      type_int = 5
c      print *,"look b",type_sum,len(type_sum)
c      print *,"look a",type_double,len(type_double)

      if (.not. convertdouble) then
c         dp_type = MPI_DOUBLE_PRECISION
      dp_type = 17
      else
c         dp_type = MPI_REAL
      dp_type = 13
      endif

      if (node.eq.root)  then

c   Because the size of the problem is too large to store in a 32-bit
c   integer for some classes, we put it into a string (for printing).
c   Have to strip off the decimal point put in there by the floating
c   point print statement (internal file)

          write(*, 1000)
          write(size, '(f15.0)' ) 2.d0**(m+1)
          j = 15
          if (size(j:j) .eq. '.') j = j - 1
          write (*,1001) size(1:j)
          write(*, 1003) no_nodes

 1000 format(/,' NAS Parallel Benchmarks 3.3 -- EP Benchmark',/)
 1001     format(' Number of random numbers generated: ', a15)
 1003     format(' Number of active processes:         ', 2x, i13, /)

          open (unit=2,file='timer.flag',status='old',iostat=fstatus)
          timers_enabled = .false.
          if (fstatus .eq. 0) then
             timers_enabled = .true.
             close(2)
          endif
      endif

c      print *,"print_cnt before",cnt1,cnt2,type_sum
c      call print_cnt(cnt4,cnt5,cnt6)
c      print *,"print_cnt after",cnt1,cnt2,type_sum

      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call mpi_bcast(timers_enabled, 1, MPI_LOGICAL, root, 
c     >               MPI_COMM_WORLD, ierr)
      call http_bcast(node, root, no_nodes, timers_enabled, 1, 
     >                6, bc_num)

c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_Bcast", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_Bcast", time_end_s - time_begin_s
#endif
c     時間計測用のフォーマット
 111  format('ID', i0, ': ', a, ' ', f8.6,'sec')

      bc_num = bc_num + 1

      verified = .false.

c   Compute the number of "batches" of random number pairs generated 
c   per processor. Adjust if the number of processors does not evenly 
c   divide the total number

      np = nn / no_nodes
      no_large_nodes = mod(nn, no_nodes)
      if (node .lt. no_large_nodes) then
         np_add = 1
      else
         np_add = 0
      endif
      np = np + np_add

      if (np .eq. 0) then
         write (6, 1) no_nodes, nn
 1       format ('Too many nodes:',2i6)
         ierrcode = 1
c         call mpi_abort(MPI_COMM_WORLD,ierrcode,ierr)
         stop
      endif

c   Call the random number generator functions and initialize
c   the x-array to reduce the effects of paging on the timings.
c   Also, call all mathematical functions that are used. Make
c   sure these initializations cannot be eliminated as dead code.

      call vranlc(0, dum(1), dum(2), dum(3))
      dum(1) = randlc(dum(2), dum(3))
      do 5    i = 1, 2*nk
         x(i) = -1.d99
 5    continue
      Mops = log(sqrt(abs(max(1.d0,1.d0))))

c---------------------------------------------------------------------
c      Synchronize before placing time stamp
c---------------------------------------------------------------------
      do i = 1, t_last
         call timer_clear(i)
      end do
      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c     call mpi_barrier(MPI_COMM_WORLD, ierr)
c      call barrier(node, no_nodes, bc_num)
      call SYSTEM_CLOCK(count_end)
c      time_end_s = mpi_wtime()
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_Barrier", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_Barrier", time_end_s - time_begin_s
#endif

      bc_num = bc_num + 1
      call timer_start(1)

      t1 = a
      call vranlc(0, t1, a, x)

c   Compute AN = A ^ (2 * NK) (mod 2^46).

      t1 = a

      do 100 i = 1, mk + 1
         t2 = randlc(t1, t1)
 100  continue

      an = t1
      tt = s
      gc = 0.d0
      sx = 0.d0
      sy = 0.d0

      do 110 i = 0, nq - 1
         q(i) = 0.d0
 110  continue

c   Each instance of this loop may be performed independently. We compute
c   the k offsets separately to take into account the fact that some nodes
c   have more numbers to generate than others

      if (np_add .eq. 1) then
         k_offset = node * np -1
      else
         k_offset = no_large_nodes*(np+1) + (node-no_large_nodes)*np -1
      endif

      do 150 k = 1, np
         kk = k_offset + k 
         t1 = s
         t2 = an

c        Find starting seed t1 for this kk.

         do 120 i = 1, 100
            ik = kk / 2
            if (2 * ik .ne. kk) t3 = randlc(t1, t2)
            if (ik .eq. 0) goto 130
            t3 = randlc(t2, t2)
            kk = ik
 120     continue

c        Compute uniform pseudorandom numbers.
 130     continue

         if (timers_enabled) call timer_start(t_randn)
         call vranlc(2 * nk, t1, a, x)
         if (timers_enabled) call timer_stop(t_randn)

c        Compute Gaussian deviates by acceptance-rejection method and 
c        tally counts in concentric square annuli.  This loop is not 
c        vectorizable. 

         if (timers_enabled) call timer_start(t_gpairs)

         do 140 i = 1, nk
            x1 = 2.d0 * x(2*i-1) - 1.d0
            x2 = 2.d0 * x(2*i) - 1.d0
            t1 = x1 ** 2 + x2 ** 2
            if (t1 .le. 1.d0) then
               t2   = sqrt(-2.d0 * log(t1) / t1)
               t3   = (x1 * t2)
               t4   = (x2 * t2)
               l    = max(abs(t3), abs(t4))
               q(l) = q(l) + 1.d0
               sx   = sx + t3
               sy   = sy + t4
            endif
 140     continue

         if (timers_enabled) call timer_stop(t_gpairs)

 150  continue


      if (timers_enabled) call timer_start(t_rcomm)
      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call mpi_allreduce(sx, x, 1, dp_type,
c     >                   MPI_SUM, MPI_COMM_WORLD, ierr)
      call http_allreduce(node, 0, no_nodes, sx, x, 1, dp_type,
     >                   3, all_num)
      call SYSTEM_CLOCK(count_end)
c      time_end_s = mpi_wtime()
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_AllReduce1", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_AllReduce1", time_end_s - time_begin_s
#endif

      all_num = all_num+1

      sx = x(1)
      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call mpi_allreduce(sy, x, 1, dp_type,
c     >                   MPI_SUM, MPI_COMM_WORLD, ierr)
      call http_allreduce(node, 0, no_nodes, sy, x, 1, dp_type,
     >                   3, all_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_AllReduce2", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_AllReduce2", time_end_s - time_begin_s
#endif

      all_num = all_num+1

      sy = x(1)
      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call mpi_allreduce(q, x, nq, dp_type,
c     >                   MPI_SUM, MPI_COMM_WORLD, ierr)
      call http_allreduce(node, 0, no_nodes, q, x, nq, dp_type,
     >                   3, all_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_AllReduce3", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_AllReduce3", time_end_s - time_begin_s
#endif

      all_num = all_num+1

      if (timers_enabled) call timer_stop(t_rcomm)

      do i = 1, nq
         q(i-1) = x(i)
      enddo

      do 160 i = 0, nq - 1
        gc = gc + q(i)
 160  continue

      call timer_stop(1)
      tm  = timer_read(1)

      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call mpi_allreduce(tm, x, 1, dp_type,
c     >                   MPI_MAX, MPI_COMM_WORLD, ierr)
      call http_allreduce(node, 0, no_nodes, tm, x, 1, dp_type,
     >                   MPI_MAX, all_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_AllReduce4", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_AllReduce4", time_end_s - time_begin_s
#endif

      all_num = all_num+1

      tm = x(1)

      if (node.eq.root) then
         nit=0
         verified = .true.
         if (m.eq.24) then
            sx_verify_value = -3.247834652034740D+3
            sy_verify_value = -6.958407078382297D+3
         elseif (m.eq.25) then
            sx_verify_value = -2.863319731645753D+3
            sy_verify_value = -6.320053679109499D+3
         elseif (m.eq.28) then
            sx_verify_value = -4.295875165629892D+3
            sy_verify_value = -1.580732573678431D+4
         elseif (m.eq.30) then
            sx_verify_value =  4.033815542441498D+4
            sy_verify_value = -2.660669192809235D+4
         elseif (m.eq.32) then
            sx_verify_value =  4.764367927995374D+4
            sy_verify_value = -8.084072988043731D+4
         elseif (m.eq.36) then
            sx_verify_value =  1.982481200946593D+5
            sy_verify_value = -1.020596636361769D+5
         elseif (m.eq.40) then
            sx_verify_value = -5.319717441530D+05
            sy_verify_value = -3.688834557731D+05
         else
            verified = .false.
         endif
         if (verified) then
            sx_err = abs((sx - sx_verify_value)/sx_verify_value)
            sy_err = abs((sy - sy_verify_value)/sy_verify_value)
            verified = ((sx_err.le.epsilon) .and. (sy_err.le.epsilon))
         endif
         Mops = 2.d0**(m+1)/tm/1000000.d0

         write (6,11) tm, m, gc, sx, sy, (i, q(i), i = 0, nq - 1)
 11      format ('EP Benchmark Results:'//'CPU Time =',f10.4/'N = 2^',
     >           i5/'No. Gaussian Pairs =',f15.0/'Sums = ',1p,2d25.15/
     >           'Counts:'/(i3,0p,f15.0))

         call print_results('EP', class, m+1, 0, 0, nit, npm, 
     >                      no_nodes, tm, Mops, 
     >                      'Random numbers generated', 
     >                      verified, npbversion, compiletime, cs1,
     >                      cs2, cs3, cs4, cs5, cs6, cs7)

      endif


      if (.not.timers_enabled) goto 999

      do i = 1, t_last
         t1m(i) = timer_read(i)
      end do
      t1m(t_last+2) = t1m(t_rcomm)
      t1m(t_last+1) = t1m(t_total) - t1m(t_last+2)

      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call MPI_Reduce(t1m, tsum,  t_last+2, dp_type, MPI_SUM, 
c     >                0, MPI_COMM_WORLD, ierr)
      call http_reduce(node, 0,  no_nodes, t1m, 
     >                tsum, t_last+2, dp_type, 3,re_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_Reduce1", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_Reduce1", time_end_s - time_begin_s
#endif
      re_num = re_num+1

      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call MPI_Reduce(t1m, tming, t_last+2, dp_type, MPI_MIN, 
c     >                0, MPI_COMM_WORLD, ierr)
      call http_reduce(node, 0, no_nodes, t1m,
     >                tming, t_last+2, dp_type, 2, re_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_Reduce2", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_Reduce2", time_end_s - time_begin_s
#endif
    
      re_num = re_num+1

      call SYSTEM_CLOCK(count_start, count_rate)
c      time_begin_s = mpi_wtime()
      time_begin_s = real(count_start) / real(count_rate)
c      call MPI_Reduce(t1m, tmaxg, t_last+2, dp_type, MPI_MAX, 
c     >                0, MPI_COMM_WORLD, ierr)
      call http_reduce(node, 0, no_nodes, t1m,
     >                tmaxg, t_last+2, dp_type, 1, re_num)
c      time_end_s = mpi_wtime()
      call SYSTEM_CLOCK(count_end)
      time_end_s = real(count_end) / real(count_rate)
      ! add
#ifdef MYLOG
      call log_fprintf(node,"MPI_Reduce3", time_end_s - time_begin_s)
#else
      write(*,111) node,"MPI_Reduce3", time_end_s - time_begin_s
#endif

      re_num = re_num+1

      if (node .eq. 0) then
         write(*, 800) no_nodes
         do i = 1, t_last+2
            tsum(i) = tsum(i) / no_nodes
            write(*, 810) i, t_recs(i), tming(i), tmaxg(i), tsum(i)
         end do
      endif
 800  format(' nprocs =', i6, 11x, 'minimum', 5x, 'maximum', 
     >       5x, 'average')
 810  format(' timer ', i2, '(', A8, ') :', 3(2x,f10.4))

 999  continue
#ifdef MYLOG
      call log_fclose()
#endif
c      call mpi_finalize(ierr)

      end
