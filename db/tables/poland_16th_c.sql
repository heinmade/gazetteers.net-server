CREATE TABLE public.poland_16th_c (
    objectid integer NOT NULL,
    wkb_geometry bytea,
    nazwa_wspolczesna character varying,
    nazwa_16w character varying,
    charakter_osady character varying,
    rodzaj_wlasnosci character varying,
    parafia character varying,
    powiat character varying,
    wojewodztwo character varying,
    funkcje_centralne_panstwowe character varying,
    funkcje_centralne_koscielne character varying,
    prng integer,
    lat double precision,
    lon double precision,
    coord public.geometry(Point,4326),
    idx integer
);
