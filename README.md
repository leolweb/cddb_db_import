# cddb_db_import.php

Scripts to import CDDB entries in sqlite db.

There are 2 scripts with different approach, once and parallel.


### Requirements

- sqlite3
- php7

for terminal multiplexer

- bash
- screen

for pwsh

- powershell>=5


### Import sql schema to create a new sqlite db

```console
~$ sqlite3

    > .open cddb_db.sqlite
    > .read cddb_db_schema.sql  
    > .quit
```

 

⚠️ Please note:

These scripts will do a lot of I/O with a large amount of data, they could crash the system or destroy disks, be careful!

 

### Run the script

**in bash (w/ screen mux):**

parallel approach:

	~$ ./import_parallel.sh

once approach:

	~$ ./import_once.sh


**in pwsh (w/ workflow):**

parallel approach:

	PS> ./import_parallel.ps1

once approach:

	PS> ./import_once.ps1



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

 

|parallel (bash + screen)|parallel (pwsh)|once (bash)|
|-|-|-|
|[![parallel (bash + screen)](../res/parallel_bash-screen.jpg)](https://raw.githubusercontent.com/leolweb/cddb_db_import/res/parallel_bash-screen.jpg)|[![parallel (pwsh)](../res/parallel_pwsh.jpg)](https://raw.githubusercontent.com/leolweb/cddb_db_import/res/parallel_pwsh.jpg)|[![once bash](../res/once_bash.jpg)](https://raw.githubusercontent.com/leolweb/cddb_db_import/res/once_bash.jpg)|

 

#### Utils:

- [FreeDB source code repository](http://ftp.freedb.org/pub/freedb/)
- [libcddb source code repository](http://libcddb.sourceforge.net/)
- [cdrip source code sample](http://www.leapsecond.com/tools/cdrip.c)
- [EasyTAG source code sample](https://github.com/GNOME/easytag/blob/master/src/cddb_dialog.c)


#### Tips:

- [php multithreading](http://php.net/manual/en/intro.pthreads.php)
- [parallel](https://www.gnu.org/software/parallel/)
- [osascript](https://developer.apple.com/library/archive/documentation/AppleScript/Conceptual/AppleScriptLangGuide/introduction/ASLR_intro.html)
- [automator](https://developer.apple.com/documentation/automator)
- [pwsh multithread](https://docs.microsoft.com/en-us/powershell/scripting/core-powershell/workflows-guide)

 

## License

[MIT](LICENSE)
