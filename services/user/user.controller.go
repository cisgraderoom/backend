package user

import (
	"github.com/gofiber/fiber/v2"
)

// Register - function fro register
func Register(c *fiber.Ctx) error {
	return c.JSON(fiber.Map{
		"status": "register success",
	})
}

func Login(c *fiber.Ctx) error {
	return c.JSON(fiber.Map{
		"status": "login success",
	})
}
