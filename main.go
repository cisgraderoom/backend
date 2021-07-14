package main

import (
	"cisclassroom/services"
	"net/http"

	"github.com/gin-gonic/gin"
)

func main() {
	router := gin.Default()

	router.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status":  http.StatusOK,
			"message": "Everything is Fine 😎",
		})
	})

	api := router.Group("/api")
	v1 := api.Group("/v1")
	services.Setup(v1)
	router.Run(":8080")
}
