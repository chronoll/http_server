#include <stdio.h>
#include <stdlib.h>

#define SIZE 3

int main(int argc, char *argv[]) {
    if (argc < 2) {
        printf("Usage: %s <rank>\n", argv[0]);
        return 1;
    }

    int rank = atoi(argv[1]);

    int matrix[SIZE][SIZE] = {
        {1, 2, 3},
        {4, 5, 6},
        {7, 8, 9}
    };

    int answer[SIZE][SIZE] = {
        {0, 0, 0},
        {0, 0, 0},
        {0, 0, 0},
    };

    for (int i = 0; i < SIZE; i++) {
        answer[rank][i] = 0;
        for (int j = 0; j < SIZE; j++) {
            answer[rank][i] += matrix[rank][j] * matrix[j][i];
        }
    }

    for (int i = 0; i < SIZE; i++) {
        for (int j = 0; j < SIZE; j++) {
            printf("%d ", answer[i][j]);
        }
        printf("\n");
    }

    return 0;
}

