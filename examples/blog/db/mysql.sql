DROP TABLE IF EXISTS `blog`;

CREATE TABLE `blog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `permalink` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permalink` (`permalink`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `blog` (`id`, `permalink`, `date`, `title`, `content`)
VALUES
	(1,'i-love-bacon','2014-08-01 15:50:39','i love bacon','all i can really tell you is that the love of bacon is genetic. if you wernt born with the love, you will never find it. so dont try. and leave all the bacon for me. \n\nthanks.'),
	(2,'fully-cooked-bacon','2014-08-01 16:20:14','fully cooked bacon','this is a mystery to me. any self respecting human would not consume such things. ');
