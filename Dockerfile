# Start from golang base image
FROM golang:1.16-alpine as builder
# Set the current working directory inside the container
WORKDIR /app
# Environment
ENV GO_ENV=development \
    APP_ENV=development \
    TZ=Asia/Bangkok
# Install git.
# Git is required for fetching the dependencies.
# RUN apk update && apk add --no-cache git
# Copy go mod and sum files 
# COPY go.mod go.sum ./
# Download all dependencies. Dependencies will be cached if the go.mod and the go.sum files are not changed
# RUN go mod download
# Copy the source from the current directory to the working Directory inside the container
COPY . .
# Download all dependencies. Dependencies will be cached if the go.mod and the go.sum files are not changed
RUN go mod download
# Build the Go app
# RUN go build -o storage
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o cisrgraderoom .
### --- End of Builder ---

# Start a new stage from scratch
FROM golang:1.16-alpine
WORKDIR /app
# Copy the Pre-built binary file from the previous stage. Observe we also copied the .env file
COPY --from=builder /app/cisrgraderoom .
# Expose port 3000 to the outside world
EXPOSE 3000
# Command to run the executable
CMD ["./cisrgraderoom"]
