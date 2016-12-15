CREATE TABLE /*_*/pageprotect (
  ppt_page int unsigned NOT NULL,
  ppt_permission varchar(30) NOT NULL,
  ppt_group int unsigned NOT NULL
) /*$wgDBTableOptions*/;
CREATE INDEX /*i*/ppt_page ON /*_*/pageprotect(ppt_page);
CREATE INDEX /*i*/ppt_page_perm ON /*_*/pageprotect(ppt_page, ppt_permission);
CREATE INDEX /*i*/ppt_page_group ON /*_*/pageprotect(ppt_page, ppt_group);
