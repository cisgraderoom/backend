# Start from golang base image
FROM golang:1.16-alpine as builder
WORKDIR /app
RUN go get github.com/cespare/reflex
COPY . .
ENTRYPOINT ["reflex", "-c", "reflex.conf"]
