package user

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// Register - function fro register
func Register(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"status": "register success",
	})
}
