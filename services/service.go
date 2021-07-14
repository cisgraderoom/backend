package services

import (
	"cisclassroom/database"
	"cisclassroom/services/user"

	"github.com/gin-gonic/gin"
)

// Setup - setup all service
func Setup(r *gin.RouterGroup) {
	database.SetupDB()
	user.UserService(r)
}
