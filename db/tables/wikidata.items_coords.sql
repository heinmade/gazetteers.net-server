CREATE TABLE wikidata.items_coords (
    wdid character varying,
    coordstring character varying,
    lat double precision,
    lon double precision,
    geog public.geography,
    label_en character varying,
    region character varying,
    sparqlfilter character varying,
    geom public.geometry(Point,4326)
);
