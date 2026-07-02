FROM golang:1.23 AS build
WORKDIR /src

COPY go.mod go.sum .
RUN go mod download

COPY main.go .
COPY aws ./aws
COPY azure ./azure
COPY gcp ./gcp
COPY shared ./shared
RUN CGO_ENABLED=0 go build -o /authlab-go .

FROM gcr.io/distroless/static-debian12
COPY --from=build /authlab-go /authlab-go

EXPOSE 8005
ENTRYPOINT ["/authlab-go"]
