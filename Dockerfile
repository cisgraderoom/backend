##########################################
####### CisClassroom API Service #########
##########################################
FROM golang:1.16-alpine
ENV GO_ENV=development \
    APP_ENV=development \
    TZ=Asia/Bangkok

WORKDIR /app

COPY go.mod .
COPY go.sum .
RUN go mod download

COPY *.go .
RUN go build -o /cisclassroom-api

CMD ["/cisclassroom-api"]

EXPOSE 8080