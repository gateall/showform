
# STAGE 09 - 템플릿 시스템 구축

## 목표

ShowForm의 핵심 템플릿 엔진 구축

업종에 따라 자동으로 랜딩페이지 구조를 출력한다.

---

## 지원 템플릿

### TEMPLATE A

서비스 판매형

template_type

service

대상

* 누수
* 방수
* 설비
* 이사
* 청소
* 광고대행

---

### TEMPLATE B

병원형

template_type

hospital

대상

* 병원
* 치과
* 한의원
* 요양병원
* 건강검진

---

### TEMPLATE C

지역업체형

template_type

local

대상

* 식당
* 카페
* 미용실
* 펜션
* 카센터

---

## 템플릿 파일 구조

/page/templates/

service.php

hospital.php

local.php

---

## 공통 섹션

1. Hero
2. 신뢰지표
3. 문제제기
4. 해결방법
5. 사례
6. 후기
7. FAQ
8. CTA
9. 문의폼

---

## 템플릿 분기

landing.php

if(service)

service.php

if(hospital)

hospital.php

if(local)

local.php

---

## 완료 기준

* 템플릿 자동 분기
* 모바일 반응형
* 템플릿 추가 가능 구조
