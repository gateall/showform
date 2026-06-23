
# STAGE 02 - 관리자 구축

## 목표

관리자가 랜딩페이지를 생성, 수정, 삭제할 수 있도록 한다.

---

## 관리자 경로

/adm/landing/

---

## 생성 파일

landing_list.php

landing_form.php

landing_update.php

landing_delete.php

landing_view.php

---

## 랜딩 목록

출력

* 번호
* 템플릿
* 업종
* 회사명
* 전화번호
* 등록일
* 공개상태

기능

* 등록
* 수정
* 삭제
* 미리보기

---

## 랜딩 등록

입력 항목

### 기본정보

* 템플릿 선택
* 업종 선택
* 회사명
* 대표자
* 전화번호
* 주소
* 지역명
* 카카오 URL

### 콘텐츠

* 소개글
* 메인문구
* 보조문구

### 디자인

* 대표 이미지
* 테마 색상

### 운영

* 공개 여부

---

## 템플릿

service

hospital

local

---

## 이미지 업로드

경로

/data/landing/

지원

* jpg
* png
* webp

---

## 저장 기능

신규

INSERT

수정

UPDATE

---

## 삭제 기능

Soft Delete

is_active = 0

---

## 완료 기준

* 랜딩 생성 가능
* 수정 가능
* 삭제 가능
* 공개/비공개 가능
* 이미지 업로드 가능
* 모바일 관리자 지원
