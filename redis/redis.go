package redis

import (
	"context"
	"errors"
	"fmt"
	"os"

	"github.com/go-redis/redis/v8"
)

var (
	ErrNil = errors.New("no matching record found in redis database")
	Ctx    = context.TODO()
)

var Cache *redis.Client

func Connect() *redis.Client {
	return Cache
}

func SetupCache() {
	host := os.Getenv("REDIS_HOST")
	port := os.Getenv("REDIS_PORT")
	password := os.Getenv("REDIS_PASSWORD")
	client := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%s", host, port),
		Password: password,
		DB:       0, // use default DB
	})
	if err := client.Ping(Ctx).Err(); err != nil {
		panic(err)
	}
	Cache = client
}
