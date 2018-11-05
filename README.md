# cddb_db_import.php

Script to import CDDB entries in sqlite db.


### Import sql schema to create a new sqlite db

```console
~$ sqlite3

    > .open cddb_db.sqlite
    > .read cddb_db_schema.sql  
    > .quit
```


### Run the script

	~$ php cddb_db_import.php



### Some utilization examples

```sql
  SELECT * FROM "TRACKS" LEFT JOIN "ALBUMS" ON TRACKS.ID=ALBUMS.ID WHERE DT1 LIKE "%bob%" LIMIT 100;
```

```sql
  SELECT * FROM "TRACKS" WHERE TT0 LIKE "%dog%" LIMIT 100;
```

```sql
  SELECT * FROM "ALBUMS" WHERE DT1 LIKE "%puppy%" LIMIT 100;
```


_Utils:_

- [FreeDB source code repository](http://ftp.freedb.org/pub/freedb/)
- [libcddb source code repository](http://libcddb.sourceforge.net/)
- [cdrip source code sample](http://www.leapsecond.com/tools/cdrip.c)
- [EasyTAG source code sample](https://github.com/GNOME/easytag/blob/master/src/cddb_dialog.c)


## License

[MIT](LICENSE)
