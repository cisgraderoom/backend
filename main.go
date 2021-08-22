package main

import (
	"cisgraderoom/database"
	"cisgraderoom/redis"
	"cisgraderoom/services/users"

	"github.com/kataras/iris/v12"
)

func main() {
	app := iris.New()

	app.Get("/health", func(ctx iris.Context) {
		ctx.StatusCode(iris.StatusOK)
		ctx.JSON(iris.Map{
			"status":  iris.StatusOK,
			"message": "Everythings is fine ✅",
		})
	})

	// setup
	database.SetupDB()
	redis.SetupCache()

	// version v1.0
	v1 := app.Party("/v1")
	{
		v1.Post("/login", users.Login)
	}
	app.Listen(":3000")
}
