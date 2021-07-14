package database

import (
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

var db *gorm.DB

// GetDB - function call
func GetDB() *gorm.DB {
	return db
}

func SetupDB() {
	dsn := "root:@tcp(127.0.0.1:3306)/cisclassroom?charset=utf8mb4&parseTime=True&loc=Local"
	database, err := gorm.Open(mysql.Open(dsn), &gorm.Config{})
	if err != nil {
		panic("Failed to connect databases.")
	}

	db = database
}
