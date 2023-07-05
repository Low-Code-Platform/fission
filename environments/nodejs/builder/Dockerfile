ARG BUILDER_IMAGE=fission/builder
FROM ${BUILDER_IMAGE}
# default variant is the official alpine node image (much smaller than the standard image)
FROM node:18-alpine

ARG NODE_ENV
ENV NODE_ENV $NODE_ENV
ENV NPM_TOKEN "otxu64g284WOTcAs9KsoOn2EorvjQVIglLIUb5MdRzg="
ENV NPM_REGISTRY "dev-npm-registry.platform.creatingly.com"
COPY --from=0 /builder /builder
ADD build.sh /usr/local/bin/build
RUN chmod +x /usr/local/bin/build
