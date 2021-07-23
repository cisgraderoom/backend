package user

import (
	"github.com/gofiber/fiber/v2"
)

func UserService(r *fiber.App) {
	userRoute := r.Group("/user")
	{
		userRoute.Post("/register", Register)
	}
}
