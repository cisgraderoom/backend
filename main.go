package main

import (
	"github.com/gin-gonic/gin"
	"net/http"
)

func main() {
	router := gin.Default()

	router.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
            "status": http.StatusOK,
            "message": "Everything is Fine 😎",
        })
	})

    router.Run(":8080")
}

