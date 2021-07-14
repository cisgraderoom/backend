package user

import "github.com/gin-gonic/gin"

func UserService(r *gin.RouterGroup) {
	userRoute := r.Group("/user")
	{
		userRoute.POST("/register", Register)
	}
}
