#!/bin/sh
cd ${SRC_PKG}
npm install && npm run test && cp -r ${SRC_PKG} ${DEPLOY_PK
