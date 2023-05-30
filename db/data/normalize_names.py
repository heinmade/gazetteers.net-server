import sys
import psycopg2
import re
from unidecode import unidecode

conn = psycopg2.connect("dbname=gazetteers")

def normalize_string(str):
    str_ascii = unidecode(str)
    str_lowercase = str.lower()
    str_ascii_lowercase = str_ascii.lower() 
    return (str_ascii, str_lowercase, str_ascii_lowercase)
    
def create_names(select, namestab, idcolumn):
    cursor_word = conn.cursor()
    sql_word = "INSERT INTO " + namestab + " (" + idcolumn + ", name, name_ascii, name_lowercase, name_ascii_lowercase) VALUES (%s, %s, %s, %s, %s);"
    cursor = conn.cursor()
    sql = select
    cursor.execute(sql);
    result = cursor.fetchall()
    for row in result:
        ort = row[0]
        name = row[1]
        name_ascii = unidecode(name)
        name_lowercase = name.lower()
        name_ascii_lowercase = name_ascii.lower() 
        data = (ort, name, name_ascii, name_lowercase, name_ascii_lowercase)
        cursor_word.execute(sql_word, data)
    conn.commit()
    cursor_word.close()
    
def split_name(name):
   regex_separators = "\s+|,|\.|\!|\:|;|/|-|–|\(|\)|\[|\]";
   words = re.split(regex_separators, name)
   words = list(filter(None, words)) 
   return words
   
def create_nameparts(namestab, namepartstab, idcolumn):
    cursor_word = conn.cursor()
    sql_word = "INSERT INTO " + namepartstab + " (" + idcolumn + ", name, name_ascii, name_lowercase, name_ascii_lowercase, fullname) VALUES (%s, %s, %s, %s, %s, %s);"
    cursor = conn.cursor()
    sql = "SELECT " + idcolumn + ", name FROM " + namestab
    cursor.execute(sql);
    result = cursor.fetchall()
    for row in result:
        ort = row[0]
        fullname = row[1]
        words = split_name(fullname)
        words.insert(0, fullname)
        for word in words:
            name = word
            name_ascii = unidecode(name)
            name_lowercase = name.lower()
            name_ascii_lowercase = name_ascii.lower() 
            data = (ort, name, name_ascii, name_lowercase, name_ascii_lowercase, fullname)
            cursor_word.execute(sql_word, data)
    conn.commit()
    cursor_word.close()
    
if __name__ == "__main__":
    create_names("select distinct wdid, label from wikidata.labels order by wdid", "wikidata_partdump.labels_names", "wdid");
    create_nameparts("wikidata_partdump.labels_names", "wikidata.labels_nameparts", "wdid");

