package schemas

import (
	"time"
)

type UserSchema struct {
	Username string    `db:"username"`
	Password string    `db:"password"`
	Name     string    `db:"name"`
	Role     string    `db:"role"`
	Ip       string    `db:"ip"`
	CreateAt time.Time `db:"create_at"`
	UpdateAt time.Time `db:"update_at"`
	Status   string    `db:"status"`
}

type UserPublicSchema struct {
	Username string `json:"username"`
	Name     string `json:"name"`
	Role     string `json:"role"`
	Status   string `json:"status"`
}
