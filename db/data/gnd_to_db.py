import xml.etree.ElementTree as ET
import psycopg2

namespaces = {
    "schema": "http://schema.org/",
    "gndo": "https://d-nb.info/standards/elementset/gnd#",
    "lib": "http://purl.org/library/",
    "owl": "http://www.w3.org/2002/07/owl#",
    "xsd": "http://www.w3.org/2001/XMLSchema#",
    "skos": "http://www.w3.org/2004/02/skos/core#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#",
    "editeur": "https://ns.editeur.org/thema/",
    "geo": "http://www.opengis.net/ont/geosparql#",
    "umbel": "http://umbel.org/umbel#",
    "rdau": "http://rdaregistry.info/Elements/u/",
    "sf": "http://www.opengis.net/ont/sf#",
    "bflc": "http://id.loc.gov/ontologies/bflc/",
    "dcterms": "http://purl.org/dc/terms/",
    "vivo": "http://vivoweb.org/ontology/core#",
    "isbd": "http://iflastandards.info/ns/isbd/elements/",
    "foaf": "http://xmlns.com/foaf/0.1/",
    "mo": "http://purl.org/ontology/mo/",
    "marcRole": "http://id.loc.gov/vocabulary/relators/",
    "agrelon": "https://d-nb.info/standards/elementset/agrelon#",
    "dcmitype": "http://purl.org/dc/dcmitype/",
    "dbp": "http://dbpedia.org/property/",
    "dnbt": "https://d-nb.info/standards/elementset/dnb#",
    "madsrdf": "http://www.loc.gov/mads/rdf/v1#",
    "dnb_intern": "http://dnb.de/",
    "rdf": "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "v": "http://www.w3.org/2006/vcard/ns#",
    "wdrs": "http://www.w3.org/2007/05/powder-s#",
    "ebu": "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#",
    "bibo": "http://purl.org/ontology/bibo/",
    "gbv": "http://purl.org/ontology/gbv/",
    "dc": "http://purl.org/dc/elements/1.1/"
}

conn = psycopg2.connect("dbname=gazetteers")
cursor = conn.cursor()

q_name =  "insert into gnd.gnd_name (gndid, name, type) VALUES (%s, %s, %s);"
q_location =  "insert into gnd.gnd_location (gndid, lat, lon) VALUES (%s, %s, %s);"

def process_elem(entelem):
    if dump: print
    if dump: print("entelem: " + str(entelem))
    type_tag = entelem
    if dump: print("type_tag: " + str(type_tag))
    if dump: print("type_tag.attrib: " + str(type_tag.attrib))
    value = type_tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about']
    if dump: print("type_tag.attrib.value: " + str(value))
    p = value.rfind("/")
    gndid = value[p+1:] 
    if dump: print("gndid: " + str(gndid))
    if dump: print()
    for tag in type_tag.findall('owl:sameAs', namespaces):
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        if dump: print("same as: " + str(value))
        p = value.rfind("#")
        sameas = value[p+1:]
        if dump: print("sameas: " + str(sameas))
        if dump: print()
        cursor.execute("insert into gnd.gnd_sameas (gndid, sameas) VALUES (%s, %s);", (gndid, sameas))    
    for tag in type_tag.findall('gndo:oldAuthorityNumber', namespaces):
        if dump: print("oldAuthorityNumber: " + tag.text)
    for tag in type_tag.findall('gndo:geographicAreaCode', namespaces):
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("#")
        areacode = value[p+1:] 
        cursor.execute("insert into gnd.gnd_entareacode (gndid, areacode) VALUES (%s, %s);", (gndid, areacode))
    for tag in type_tag.findall('rdf:type', namespaces):
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        if dump: print("type: " + str(value))
        p = value.rfind("#")
        atype = value[p+1:] 
        if dump: print("type: " + str(atype))
        if dump: print()
        cursor.execute("insert into gnd.gnd_enttype (gndid, type) VALUES (%s, %s);", (gndid, atype))
    for tag in type_tag.findall('gndo:preferredNameForThePlaceOrGeographicName', namespaces):
        if dump: print("preferredName: " + tag.text)
        name = tag.text.strip()
        cursor.execute(q_name, (gndid, name, True))
    for tag in type_tag.findall('gndo:variantNameForThePlaceOrGeographicName', namespaces):
        if dump: print("variantName: " + tag.text)
        name = tag.text.strip()
        cursor.execute(q_name, (gndid, name, False))
    for tag in type_tag.findall('gndo:gndSubjectCategory', namespaces):
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("#")
        cat = value[p+1:] 
        cursor.execute("insert into gnd.gnd_entcategory (gndid, cat) VALUES (%s, %s);", (gndid, cat))
    for tag in type_tag.findall('gndo:broaderTermInstantial', namespaces):         
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("/")
        instanceof = value[p+1:] 
        cursor.execute("insert into gnd.gnd_entinstanceof (gndid, instanceof) VALUES (%s, %s);", (gndid, instanceof))
    for tag in type_tag.findall('foaf:page', namespaces):
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        if dump: print("foaf: " + str(value))
        p = value.rfind("#")
        foaf = value[p+1:]
        if dump: print("foaf: " + str(foaf))
        if dump: print()
        cursor.execute("insert into gnd.gnd_foaf (gndid, foaf) VALUES (%s, %s);", (gndid, foaf))    
    for tag in type_tag.findall('geo:hasGeometry/rdf:Description/geo:asWKT', namespaces):
        if dump: print(tag.text)
        value = tag.text
        if("Point") in value:
             p1 = value.find("(")
             p2 = value.find(")")
             coords = value[p1+1:p2].strip().split(" ")
             if(coords[0].startswith("+")): coords[0] = coords[0][1:]
             if(coords[1].startswith("+")): coords[1] = coords[1][1:] 
             if dump: print("lon: " + coords[0])
             if dump: print("lat: " + coords[1])
             cursor.execute(q_location, (gndid, coords[1], coords[0]))        
    for tag in type_tag.findall('gndo:broaderTermPartitive', namespaces):         
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("/")
        partof = value[p+1:] 
        cursor.execute("insert into gnd.gnd_entpartof (gndid, partof) VALUES (%s, %s);", (gndid, partof))
    for tag in type_tag.findall('gndo:succeedingPlaceOrGeographicName', namespaces):         
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("/")
        successor = value[p+1:] 
        cursor.execute("insert into gnd.gnd_successor (gndid, successor) VALUES (%s, %s);", (gndid, successor))
    for tag in type_tag.findall('gndo:precedingPlaceOrGeographicName', namespaces):         
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("/")
        predecessor = value[p+1:] 
        cursor.execute("insert into gnd.gnd_predecessor (gndid, predecessor) VALUES (%s, %s);", (gndid, predecessor))
    for tag in type_tag.findall('gndo:hierarchicalSuperiorOfPlaceOrGeographicName', namespaces):         
        value = tag.attrib['{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource']
        p = value.rfind("/")
        partof = value[p+1:] 
        cursor.execute("insert into gnd.gnd_entpartof2 (gndid, partof) VALUES (%s, %s);", (gndid, partof))
    if dump: print()

dump = False

ns_to_url = {}
url_to_ns = {}
elems = {}
level = 0
cnt = 0
for event, elem in ET.iterparse('gnd_geografikum/authorities-geografikum_lds.rdf', events=('start-ns', 'start', 'end')):
    cnt = cnt+1
    if event == 'start-ns':
        ns, url = elem
        ns_to_url[ns] = url
        url_to_ns[url] = ns

    if event == 'start':
        level += 1
   
    if event == 'end':
        if level == 2:
            process_elem(elem)
            elem.clear()
        level -= 1
    if cnt % 10000 == 0:
        conn.commit()
conn.commit()
cursor.close()
conn.close()
