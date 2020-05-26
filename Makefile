CC=cc
all: crypt.c
		$(CC) -shared -lcrypt crypt.c -o crypt.so
clean:
		rm crypt.so

