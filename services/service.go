package services

import (
	"cisclassroom/services/user"

	"github.com/gofiber/fiber/v2"
)

// Setup - setup all service
func Setup(r *fiber.App) {
	user.UserService(r)
}
