<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:mods="http://www.loc.gov/mods/v3"
	xmlns:etd="http://www.ndltd.org/standards/metadata/etdms/1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0" exclude-result-prefixes="etd xsi">

	<xsl:output method="xml" indent="yes" omit-xml-declaration="no"/>
	<xsl:strip-space elements="*"/>

	<!--
	  -
	  - METADATA EDITS GO BELOW HERE
	  -
	-->

	<!-- RESEARCH COLLECTION-SPECIFIC EDITS -->

		<!-- 1: Remove <name> elements with "degree grantor" as role -->
		<xsl:template match="mods:name[mods:role/mods:roleTerm = 'Degree grantor' and not(//mods:genre = 'Dissertation/Thesis')]"/>

		<!-- 2: Remove <extension> elements & children -->
		<xsl:template match="mods:extension[not(//mods:genre = 'Dissertation/Thesis')]"/>
	
		<!-- 3: normalize <typeOfResource> values -->
		<xsl:template match="mods:typeOfResource">
			<mods:typeOfResource>text</mods:typeOfResource>
			
			<xsl:if test=". != 'text'">
				<mods:genre>
					<xsl:value-of select="."/>
				</mods:genre>
			</xsl:if>
		</xsl:template>

	<!-- GENERAL/UNIVERSAL EDITS -->

		<!-- 1: Replace "University place" with "Degree grantor" -->
		<xsl:template match="text()[. = 'University place']">
			<xsl:text>Degree grantor</xsl:text>
		</xsl:template>

		<!-- 2: Remove empty <url> under <location> -->
		<xsl:template match="mods:location">
			<mods:location/>
		</xsl:template>

		<!-- 3: Remove empty "qualifier" and "keyDate" attributes from dateIssued/dateCreated -->
		<xsl:template match="@qualifier[. = '']">
			<xsl:apply-templates/>
		</xsl:template>

		<xsl:template match="@keyDate[. = 'yes']">
			<xsl:apply-templates/>
		</xsl:template>

		<!-- 4: Add/update <mods:language> -->
		<xsl:template match="mods:language">
			<mods:language>
				<mods:languageTerm type="code" authority="iso639-3">eng</mods:languageTerm>
			</mods:language>
		</xsl:template>

		<xsl:template match="mods:mods/*[position() = last() and not(//mods:language)]">
			<xsl:element name="mods:{local-name()}" namespace="http://www.loc.gov/mods/v3">
				<xsl:copy-of select="namespace::*"/>
				<xsl:apply-templates select="node()|@*"/>
			</xsl:element>

			<mods:language>
				<mods:languageTerm type="code" authority="iso639-3">eng</mods:languageTerm>
			</mods:language>
		</xsl:template>
	
		<!-- 5: Add @authority=“lcsh” to all <subject> elements -->
		<xsl:template match="mods:subject[not(@authority)]">
			<mods:subject authority="lcsh">
				<xsl:apply-templates/>
			</mods:subject>
		</xsl:template>

	<!--
	  -
	  - DO NOT MODIFY ANYTHING BELOW THIS LINE
	  - UNLESS YOU KNOW WHAT YOU ARE DOING!
	  -
	-->

	<!-- Canonical identity transform -->
	<!--
	<xsl:template match="@* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>
	-->

	<!-- Namespace-aware identity transform -->
	<!-- Copy elements -->
	<xsl:template match="*" priority="-1">
	   <xsl:element name="{name()}">
	      <xsl:apply-templates select="node()|@*"/>
	   </xsl:element>
	</xsl:template>

	<!-- Copy all other nodes -->
	<xsl:template match="node()|@*" priority="-2">
	   <xsl:copy />      
	</xsl:template>

	<!-- add namespace prefix -->
	<xsl:template match="mods:*">
		<xsl:element name="mods:{local-name()}" namespace="http://www.loc.gov/mods/v3">
			<xsl:copy-of select="namespace::*"/>
			<xsl:apply-templates select="node()|@*"/>
		</xsl:element>
	</xsl:template>

</xsl:stylesheet>
