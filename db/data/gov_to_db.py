import sys
import psycopg2
import re

conn = psycopg2.connect("dbname=gazetteers")
cursor = conn.cursor()
with open("gov-data_PL.txt") as f:
    for line in f:
        attrs = re.split(r'\t', line)
        try: float(attrs[11])
        except ValueError: attrs[11] = 0.0
        try: float(attrs[12])
        except ValueError: attrs[12] = 0.0
        query =  "INSERT INTO gov (govid, type, typeid, curname, lastgername, state, adm1, adm2, adm3, adm4, postalcode, lat, lon) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);"
        cursor.execute(query, attrs)
conn.commit()
cursor.close()
conn.close()
