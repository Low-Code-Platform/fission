#!/bin/sh
set -eou pipefail
mkdir -p ${SRC_PKG}/src/resources/static/test
mvn surefire-report:report site -DgenerateReports=false
mv ${SRC_PKG}/target/site/surefire-report.html ${SRC_PKG}/target/site/index.html
cp -r ${SRC_PKG}/target/site/* ${SRC_PKG}/src/resources/static/test/
echo "*************************"
ls ${SRC_PKG}/src/resources/static/test
echo "*************************"
mvn clean package
cp ${SRC_PKG}/target/*with-dependencies.jar ${DEPLOY_PKG}