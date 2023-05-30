import sys
import requests
import psycopg2
import re
from unidecode import unidecode

url = 'https://query.wikidata.org/sparql'
conn = psycopg2.connect("dbname=gazetteers")
sparql_regionfilters = {
    'czech_republic': '?item wdt:P17 wd:Q213',
    'estonia': '?item wdt:P17 wd:Q191',
    'hungary': '?item wdt:P17 wd:Q28',
    'kaliningrad': '?item wdt:P131* wd:Q1749',
    'latvia': '?item wdt:P17 wd:Q211',
    'lithuania': '?item wdt:P17 wd:Q37',
    'poland': '?item wdt:P17 wd:Q36',
    'slovakia': '?item wdt:P17 wd:Q214'
}
iso_lang_codes = {
    'czech_republic': 'cs',
    'estonia': 'et',
    'hungary': 'hu',
    'kaliningrad': 'ru',
    'latvia': 'lv',
    'lithuania': 'lt',
    'poland': 'pl',
    'slovakia': 'sk'   
}
headers = {'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'}
sparql_coordsfilter = '?item wdt:P625 ?coords'
sparql_typefilter = '?item wdt:P31 ?type'
sql_name = "INSERT INTO wikidata.items_names (wdid, name, lang, region, sparqlfilter) VALUES (%s, %s, %s, %s, %s);"

def create_tables():
    sql = '''
    create schema wikidata;
    create table wikidata.items(wdid varchar, label_en varchar, region varchar, sparqlfilter varchar);
    create table wikidata.items_names (wdid varchar, name varchar, name_ascii varchar, name_lowercase varchar, name_ascii_lowercase varchar, lang varchar, region varchar, sparqlfilter varchar);
    create table wikidata.items_coords(wdid varchar, coordstring varchar, lat double precision, lon double precision, geog geography, label_en varchar, region varchar, sparqlfilter varchar);
    create table wikidata.items_types(itemid varchar, typeid varchar, typelabel_en varchar, region varchar, sparqlfilter varchar);
    create table wikidata.items_refs(wdid varchar, reffed_db varchar, reffed_id varchar, region varchar, sparqlfilter varchar);
    create table wikidata.items_nameparts (wdid varchar, name varchar, name_ascii varchar, name_lowercase varchar, name_ascii_lowercase varchar, fullname varchar, lang varchar, region varchar, sparqlfilter varchar);
    '''
    cursor = conn.cursor()
    cursor.execute(sql)
    conn.commit()
    cursor.close()

def handle_id_enname_coords(region, only_dump_query=False):
    try:
        regionfilter = sparql_regionfilters[region]
    except KeyError:
        sys.exit("KeyError: '" + region + "' not found in dictionary")
    sparqlfilter = regionfilter + ' . ' + sparql_coordsfilter + ' . '
    query = '''
    SELECT ?item ?itemLabel ?coords WHERE { {
        SELECT ?item ?coords WHERE { 
    ''' + sparqlfilter + '''
        }
    } OPTIONAL { ?item rdfs:label ?itemLabel filter (lang(?itemLabel) = "en") } }
    ORDER BY ?item
    '''
    if only_dump_query:
        print(query)
        sys.exit()
    cursor_item = conn.cursor()
    cursor_name = conn.cursor()
    cursor_coords = conn.cursor()
    sql_item = "INSERT INTO wikidata.items (wdid, label_en, region, sparqlfilter) VALUES (%s, %s, %s, %s);"
    sql_coords = "INSERT INTO wikidata.items_coords (wdid, coordstring, lat, lon, label_en, region, sparqlfilter) VALUES (%s, %s, %s, %s, %s, %s, %s);"
    r = requests.get(url, params = {'format': 'json', 'query': query}, headers = headers)
    data = r.json()
    items = data['results']['bindings']
    i=0
    itemid_old = 0
    for item in items:
        i+=1
        itemid = item['item']['value']
        itemid = itemid[itemid.rfind('Q'):]
        label = ''
        if 'itemLabel' in item:
            label = item['itemLabel']['value']
        coords = item['coords']['value']
        lon = None
        lat = None
        if coords.find('Point') != -1:
            pm = coords.find(' ')
            lon = coords[coords.find('(')+1:pm]
            lat = coords[pm:-1]
        if(itemid != itemid_old):
            data = (itemid, label, region, sparqlfilter)
            cursor_item.execute(sql_item, data)
            data = (itemid, label, 'en', region, sparqlfilter)
            cursor_name.execute(sql_name, data)
        itemid_old = itemid
        data = (itemid, coords, lat, lon, label, region, sparqlfilter) 
        cursor_coords.execute(sql_coords, data)
    conn.commit()
    cursor_item.close()
    cursor_name.close
    cursor_coords.close()

def handle_type(region, only_dump_query=False):
    try:
        regionfilter = sparql_regionfilters[region]
    except KeyError:
        sys.exit("KeyError: '" + region + "' not found in dictionary")
    sparqlfilter = regionfilter + ' . ' + sparql_coordsfilter + ' . ' + sparql_typefilter  + ' . '
    query = '''
    SELECT ?item ?type ?typeLabel WHERE { {
        SELECT distinct ?item ?type WHERE {
        ''' + sparqlfilter + '''
        }
    } OPTIONAL { ?type rdfs:label ?typeLabel filter (lang(?typeLabel) = "en") } }
    ORDER BY ?item
    '''
    if only_dump_query:
        print(query)
        sys.exit()
    cursor_type = conn.cursor()
    sql_type = "INSERT INTO wikidata.items_types (itemid, typeid, typelabel_en, region, sparqlfilter) VALUES (%s, %s, %s, %s, %s);"
    r = requests.get(url, params = {'format': 'json', 'query': query}, headers = headers)
    data = r.json()
    items = data['results']['bindings']
    for item in items:
        itemid = item['item']['value']
        itemid = itemid[itemid.rfind('Q'):]
        #print(itemid)
        typeid = item['type']['value']
        typeid = typeid[typeid.rfind('Q'):]
        #print(typeid)
        typelabel = ''
        if 'typeLabel' in item:
            typelabel = item['typeLabel']['value']
        #print(typelabel)
        #print()
        data = (itemid, typeid, typelabel, region, sparqlfilter) 
        cursor_type.execute(sql_type, data)
    conn.commit() 
    cursor_type.close()

def handle_langs(region, only_dump_query=False):
    try:
        regionfilter = sparql_regionfilters[region]
        iso_lang_code = iso_lang_codes[region]
    except KeyError:
        sys.exit("KeyError: '" + region + "' not found in dictionary")
    labelvar = '?itemLabel_' +  iso_lang_code
    sparqlfilter = regionfilter + ' . ' + sparql_coordsfilter + ' . '
    query = '''
    SELECT ?item ?itemLabel_de ''' + labelvar + '''  WHERE { {
        SELECT ?item WHERE { 
        ''' + sparqlfilter + '''
    }}
    OPTIONAL { ?item rdfs:label ?itemLabel_de filter (lang(?itemLabel_de) = "de") } .
    OPTIONAL { ?item rdfs:label ''' + labelvar + ''' filter (lang(''' + labelvar + ''') = "''' + iso_lang_code + '''") }                                
    }
    ORDER BY ?item
    '''
    labelvar = labelvar[1:]
    if only_dump_query:
        print(query)
        sys.exit()
    cursor_name = conn.cursor()
    r = requests.get(url, params = {'format': 'json', 'query': query}, headers = headers)
    data = r.json()
    items = data['results']['bindings']
    for item in items:
        itemid = item['item']['value']
        itemid = itemid[itemid.rfind('Q'):]
        label_de = ''
        if 'itemLabel_de' in item:
            label_de = item['itemLabel_de']['value']
        label_country = ''
        if labelvar in item:
            label_country = item[labelvar]['value']
        if label_de != '':
            data = (itemid, label_de, 'de', region, sparqlfilter)
            cursor_name.execute(sql_name, data)
        if label_country != '':
            data = (itemid, label_country, iso_lang_code, region, sparqlfilter)
            cursor_name.execute(sql_name, data)
    conn.commit() 
    cursor_name.close()

def handle_refs(region, only_dump_query=False):
    reffed_dbs = [
        ['geonames', 'P1566'],
        ['gnd', 'P227'],
        ['gov', 'P2503']
    ]
    reffed_dbs_per_region = {    
        'poland': [['simc', 'P4046'], ['teryt', 'P1653']]   
        #'estonia': [['ehak', 'P114']]
        #'czech_republic':
        #'hungary': 
        #'kaliningrad': 
        #'latvia':
        #'lithuania':
        #'slovakia':
    }
    try:
        reffed_dbs += reffed_dbs_per_region[region]
    except KeyError:   
        print("no specific refdb for '" + region + "'")
    try:
        regionfilter = sparql_regionfilters[region]
    except KeyError:
        sys.exit("KeyError: '" + region + "' not found in dictionary")
    for reffed_db in reffed_dbs:
        dbfilter = '?item wdt:' + reffed_db[1] + ' ?reffed_id'
        sparqlfilter = regionfilter + ' . ' + sparql_coordsfilter + ' . ' + dbfilter + ' . '
        query = '''
        SELECT DISTINCT ?item ?reffed_id WHERE { 
        ''' + sparqlfilter + '''
        }
        ORDER BY ?item
        '''
        if only_dump_query:
            print(query)
            sys.exit()
        cursor_ref = conn.cursor()
        sql_ref = "INSERT INTO wikidata.items_refs (wdid, reffed_db, reffed_id, region, sparqlfilter) VALUES (%s, %s, %s, %s, %s);"
        r = requests.get(url, params = {'format': 'json', 'query': query}, headers = headers)
        data = r.json()
        items = data['results']['bindings']
        for item in items:
            itemid = item['item']['value']
            itemid = itemid[itemid.rfind('Q'):]
            label_de = ''
            reffed_id = item['reffed_id']['value']
            data = (itemid, reffed_db[0], reffed_id, region, sparqlfilter)
            cursor_ref.execute(sql_ref, data)  
        conn.commit() 
        cursor_ref.close()

def normalize_names():
    cursor_word = conn.cursor()
    sql_word = "UPDATE wikidata.items_names SET name_ascii=%s, name_lowercase=%s, name_ascii_lowercase=%s WHERE wdid=%s and lang=%s;"
    cursor = conn.cursor()
    sql = "SELECT wdid, name, lang, region, sparqlfilter FROM wikidata.items_names";
    cursor.execute(sql);
    result = cursor.fetchall()
    for row in result:
        wdid = row[0]
        name = row[1]
        lang = row[2]
        region = row[3]
        sparqlfilter = row[4]
        name_ascii = unidecode(name)
        name_lowercase = name.lower()
        name_ascii_lowercase = name_ascii.lower() 
        data = (name_ascii, name_lowercase, name_ascii_lowercase, wdid, lang)
        cursor_word.execute(sql_word, data)
    conn.commit()
    cursor_word.close()

def normalize_string(str):
    str_ascii = unidecode(str)
    str_lowercase = str.lower()
    str_ascii_lowercase = str_ascii.lower() 
    return (str_ascii, str_lowercase, str_ascii_lowercase)

def split_name(name):
   regex_separators = "\s+|,|\.|\!|\:|;|/|-|–|\(|\)|\[|\]";
   words = re.split(regex_separators, name)
   words = list(filter(None, words))
   return words

def create_nameparts():
    cursor_word = conn.cursor()
    sql_word = "INSERT INTO wikidata.items_nameparts (wdid, name, name_ascii, name_lowercase, name_ascii_lowercase, fullname, lang, region, sparqlfilter) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);"
    cursor = conn.cursor()
    sql = "SELECT wdid, name, lang, region, sparqlfilter FROM wikidata.items_names";
    cursor.execute(sql);
    result = cursor.fetchall()
    for row in result:
        wdid = row[0]
        fullname = row[1]
        lang = row[2]
        region = row[3]
        sparqlfilter = row[4]
        words = split_name(fullname)
        words.insert(0, fullname)
        for word in words:
            name = word
            name_ascii = unidecode(name)
            name_lowercase = name.lower()
            name_ascii_lowercase = name_ascii.lower() 
            data = (wdid, name, name_ascii, name_lowercase, name_ascii_lowercase, fullname, lang, region, sparqlfilter)
            cursor_word.execute(sql_word, data)
    conn.commit()
    cursor_word.close()

def create_indexes(defs):
    cursor = conn.cursor()
    for d in defs: 
        suffix = d[0]
        column = d[1]
        cursor.execute("create index i_" + suffix + "_" + column + " on wikidata.items_" + suffix + "(" + column + ");")
    conn.commit()
    cursor.close()

def create_geography():
    cursor = conn.cursor()
    cursor.execute("update wikidata.items_coords set geog = ST_SetSRID(ST_MakePoint(lon, lat), 4326)::geography;")
    conn.commit()
    cursor.close()

for region in sparql_regionfilters.keys():
    handle_id_enname_coords(region)
    handle_type(region)
    handle_langs(region)
    handle_refs(region)
create_indexes([["names", "wdid"], ["coords", "wdid"], ["refs", "wdid"], ["types", "itemid"]])
normalize_names()
create_indexes([["nameparts", "wdid"]])
create_nameparts()
create_geography()
create_indexes([["names", "name_ascii_lowercase"], ["nameparts", "name_ascii_lowercase"], ["coords", "geog"]]) 

