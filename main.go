package main

import (
	"net/http"

	"github.com/gofiber/fiber/v2"
)

func main() {
	router := fiber.New()

	router.Get("/health", func(c *fiber.Ctx) error {
		return c.JSON(fiber.Map{
			"status": http.StatusOK,
			"msg":    "Everything is fine 😎",
		})
	})

	// Database Setup
	// database.SetupDB()

	// services.Setup(v1)

	router.Listen(":3000")
}
