CREATE TABLE public.prusija_lt (
    idx integer NOT NULL,
    name_litauisch character varying,
    name_deutsch character varying,
    name_russisch_polnisch character varying,
    name_russisch character varying,
    name_polnisch character varying,
    verwaltungseinheit1 character varying,
    verwaltungseinheit2 character varying,
    koordinaten character varying,
    lat double precision,
    lon double precision,
    coord public.geometry(Point,4326),
    comment character varying(1000)
);
