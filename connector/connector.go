package connector

import (
	"cisgraderoom/database"

	"gorm.io/gorm"
)

func UserConnect() *gorm.DB {
	return database.Connect().Table("users")
}
