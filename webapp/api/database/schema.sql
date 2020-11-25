DROP TABLE IF EXISTS User;

CREATE OR REPLACE TABLE `UserRole` (
    id INT NOT NULL PRIMARY KEY,
    name VARCHAR(20) NOT NULL
);

INSERT INTO UserRole (id, name) values (1, 'USER'), (2, 'ADMIN');

CREATE OR REPLACE TABLE `User` (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    login VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    salt CHAR(10) NOT NULL,
    role_id INT NOT NULL,
    CONSTRAINT `fk_user_role` FOREIGN KEY (role_id) REFERENCES `UserRole` (id)
);

INSERT INTO User (login, password, salt, role_id) values ('admin', '', '', 2);

CREATE OR REPLACE TABLE `Session` (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    expiredAt TIMESTAMP NOT NULL,
    CONSTRAINT `fk_session_user` FOREIGN KEY (user_id) REFERENCES `User` (id)
);
