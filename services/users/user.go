package users

import (
	"cisgraderoom/connector"
	"cisgraderoom/redis"
	"cisgraderoom/schemas"
	"context"
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"strings"

	"github.com/kataras/iris/v12"
)

func getMd5Hash(text string) string {
	hasher := md5.New()
	hasher.Write([]byte(text))
	return hex.EncodeToString(hasher.Sum(nil))
}

func comparePassword(password, hash string) bool {
	return getMd5Hash(password) == hash
}

func Login(ctx iris.Context) {
	username := ctx.FormValue("username")
	password := ctx.FormValue("password")
	if strings.TrimSpace(username) == "" || strings.TrimSpace(password) == "" {
		ctx.JSON(iris.Map{
			"status": iris.StatusUnauthorized,
			"error":  "username or password is empty",
		})
		return
	}
	var user schemas.UserSchema
	connector.UserConnect().Where("username = ?", username).First(&user)
	if user.Username == "" {
		ctx.JSON(iris.Map{
			"status": iris.StatusUnauthorized,
			"error":  "username is invalid",
		})
		return
	}
	if !comparePassword(password, user.Password) {
		ctx.JSON(iris.Map{
			"status": iris.StatusUnauthorized,
			"error":  "password is invalid",
		})
		return
	}
	publicuser := loginCache(ctx, user)
	ctx.JSON(iris.Map{
		"status": iris.StatusOK,
		"data":   publicuser,
	})
}

func loginCache(ctx iris.Context, user schemas.UserSchema) schemas.UserPublicSchema {
	publicuser := schemas.UserPublicSchema{
		Username: user.Username,
		Name:     user.Name,
		Role:     user.Role,
		Status:   user.Status,
	}
	json, err := json.Marshal(publicuser)
	if err != nil {
		ctx.JSON(iris.Map{
			"status": iris.StatusInternalServerError,
			"error":  "json marshal error",
		})
		return publicuser
	}
	_, err = redis.Connect().Get(context.Background(), fmt.Sprintf("user:%s", user.Username)).Result()
	if err != nil {
		err = redis.Connect().Set(context.Background(), fmt.Sprintf("user:%s", user.Username), json, 10).Err()
		if err != nil {
			ctx.JSON(iris.Map{
				"status": iris.StatusInternalServerError,
				"error":  "redis set error",
			})
			return publicuser
		}
	}
	return publicuser
}
