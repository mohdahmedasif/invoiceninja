<?php

namespace App\Services\EDocument\Standards\Verifactu\Models;

class Desglose extends BaseXmlModel
{
    protected ?array $desgloseFactura = null;
    protected ?array $desgloseTipoOperacion = null;
    protected ?array $desgloseIVA = null;
    protected ?array $desgloseIGIC = null;
    protected ?array $desgloseIRPF = null;
    protected ?array $desgloseIS = null;
    protected ?DetalleDesglose $detalleDesglose = null;

    public function toXml(\DOMDocument $doc): \DOMElement
    {
        $root = $this->createElement($doc, 'Desglose');

        // If we have DetalleDesglose objects in the desgloseIVA array, use them
        if ($this->desgloseIVA !== null && is_array($this->desgloseIVA) && count($this->desgloseIVA) > 0) {
            foreach ($this->desgloseIVA as $detalleDesglose) {
                if ($detalleDesglose instanceof DetalleDesglose) {
                    $root->appendChild($detalleDesglose->toXml($doc));
                }
            }
            return $root;
        }

        // If we have a single DetalleDesglose object, use it
        if ($this->detalleDesglose !== null) {
            $root->appendChild($this->detalleDesglose->toXml($doc));
            return $root;
        }

        // Always create a DetalleDesglose element if we have any data
        $detalleDesglose = $this->createElement($doc, 'DetalleDesglose');

        // Handle regular invoice desglose
        if ($this->desgloseFactura !== null) {
            // Add Impuesto if present
            if (isset($this->desgloseFactura['Impuesto'])) {
                $detalleDesglose->appendChild($this->createElement($doc, 'Impuesto', $this->desgloseFactura['Impuesto']));
            } else {
                // Default Impuesto for IVA
                $detalleDesglose->appendChild($this->createElement($doc, 'Impuesto', '01'));
            }

            // Add ClaveRegimen if present
            if (isset($this->desgloseFactura['ClaveRegimen']) ) {
                $detalleDesglose->appendChild($this->createElement($doc, 'ClaveRegimen', $this->desgloseFactura['ClaveRegimen']));
            } else {
                // Default ClaveRegimen
                $detalleDesglose->appendChild($this->createElement($doc, 'ClaveRegimen', '01'));
            }

            // Add CalificacionOperacion
            $detalleDesglose->appendChild($this->createElement($doc, 'CalificacionOperacion', 
                $this->desgloseFactura['CalificacionOperacion'] ?? 'S1'));

            // Add TipoImpositivo if present
            if (isset($this->desgloseFactura['TipoImpositivo']) && $this->desgloseFactura['CalificacionOperacion'] == 'S1') {
                $detalleDesglose->appendChild($this->createElement($doc, 'TipoImpositivo', 
                    number_format((float)$this->desgloseFactura['TipoImpositivo'], 2, '.', '')));
            } 
            // else {
            //     // Default TipoImpositivo
            //     $detalleDesglose->appendChild($this->createElement($doc, 'TipoImpositivo', '0'));
            // }

            // Convert BaseImponible to BaseImponibleOimporteNoSujeto if needed
            $baseImponible = isset($this->desgloseFactura['BaseImponible']) 
                ? $this->desgloseFactura['BaseImponible'] 
                : ($this->desgloseFactura['BaseImponibleOimporteNoSujeto'] ?? '0');

            $detalleDesglose->appendChild($this->createElement($doc, 'BaseImponibleOimporteNoSujeto', 
                number_format((float)$baseImponible, 2, '.', '')));


                if(isset($this->desgloseFactura['Cuota']) && $this->desgloseFactura['CalificacionOperacion'] == 'S1'){
                    $detalleDesglose->appendChild($this->createElement($doc, 'CuotaRepercutida', 
                        number_format((float)$this->desgloseFactura['Cuota'], 2, '.', '')));
                }

            // Add TipoRecargoEquivalencia if present
            if (isset($this->desgloseFactura['TipoRecargoEquivalencia'])) {
                $detalleDesglose->appendChild($this->createElement($doc, 'TipoRecargoEquivalencia', 
                    number_format((float)$this->desgloseFactura['TipoRecargoEquivalencia'], 2, '.', '')));
            }

            // Add CuotaRecargoEquivalencia if present
            if (isset($this->desgloseFactura['CuotaRecargoEquivalencia'])) {
                $detalleDesglose->appendChild($this->createElement($doc, 'CuotaRecargoEquivalencia', 
                    number_format((float)$this->desgloseFactura['CuotaRecargoEquivalencia'], 2, '.', '')));
            }
        }
        
        // Handle simplified invoice desglose (IVA)
        if ($this->desgloseIVA !== null) {
            // If desgloseIVA is an array of arrays, handle multiple tax rates
            if (is_array(reset($this->desgloseIVA))) {
                foreach ($this->desgloseIVA as $desglose) {
                    $detalleDesglose = $this->createElement($doc, 'DetalleDesglose');

                    // Add Impuesto (required for IVA)
                    $detalleDesglose->appendChild($this->createElement($doc, 'Impuesto', $desglose['Impuesto'] ?? '01'));

                    // Add ClaveRegimen
                    $detalleDesglose->appendChild($this->createElement($doc, 'ClaveRegimen', $desglose['ClaveRegimen'] ?? '01'));

                    // Add CalificacionOperacion
                    $detalleDesglose->appendChild($this->createElement($doc, 'CalificacionOperacion', $desglose['CalificacionOperacion'] ?? 'S1'));

                    // Add TipoImpositivo if present
                    if (isset($desglose['TipoImpositivo'])) {
                        $detalleDesglose->appendChild($this->createElement($doc, 'TipoImpositivo', 
                            number_format((float)$desglose['TipoImpositivo'], 2, '.', '')));
                    }

                    // Convert BaseImponible to BaseImponibleOimporteNoSujeto if needed
                    $baseImponible = isset($desglose['BaseImponible']) 
                        ? $desglose['BaseImponible'] 
                        : ($desglose['BaseImponibleOimporteNoSujeto'] ?? '0');

                    $detalleDesglose->appendChild($this->createElement($doc, 'BaseImponibleOimporteNoSujeto', 
                        number_format((float)$baseImponible, 2, '.', '')));

                    // Convert Cuota to CuotaRepercutida if needed
                    $cuota = isset($desglose['Cuota']) 
                        ? $desglose['Cuota'] 
                        : ($desglose['CuotaRepercutida'] ?? '0');

                    $detalleDesglose->appendChild($this->createElement($doc, 'CuotaRepercutida', 
                        number_format((float)$cuota, 2, '.', '')));

                    $root->appendChild($detalleDesglose);
                }
            } else {
                // Single tax rate
                $detalleDesglose = $this->createElement($doc, 'DetalleDesglose');

                // Add Impuesto (required for IVA)
                $detalleDesglose->appendChild($this->createElement($doc, 'Impuesto', $this->desgloseIVA['Impuesto'] ?? '01'));

                // Add ClaveRegimen
                $detalleDesglose->appendChild($this->createElement($doc, 'ClaveRegimen', $this->desgloseIVA['ClaveRegimen'] ?? '01'));

                // Add CalificacionOperacion
                $detalleDesglose->appendChild($this->createElement($doc, 'CalificacionOperacion', $this->desgloseIVA['CalificacionOperacion'] ?? 'S1'));

                // Add TipoImpositivo if present
                if (isset($this->desgloseIVA['TipoImpositivo'])) {
                    $detalleDesglose->appendChild($this->createElement($doc, 'TipoImpositivo', 
                        number_format((float)$this->desgloseIVA['TipoImpositivo'], 2, '.', '')));
                }

                // Convert BaseImponible to BaseImponibleOimporteNoSujeto if needed
                $baseImponible = isset($this->desgloseIVA['BaseImponible']) 
                    ? $this->desgloseIVA['BaseImponible'] 
                    : ($this->desgloseIVA['BaseImponibleOimporteNoSujeto'] ?? '');

                $detalleDesglose->appendChild($this->createElement($doc, 'BaseImponibleOimporteNoSujeto', 
                    number_format((float)$baseImponible, 2, '.', '')));

                // Convert Cuota to CuotaRepercutida if needed
                $cuota = isset($this->desgloseIVA['Cuota']) 
                    ? $this->desgloseIVA['Cuota'] 
                    : ($this->desgloseIVA['CuotaRepercutida'] ?? '0');

                $detalleDesglose->appendChild($this->createElement($doc, 'CuotaRepercutida', 
                    number_format((float)$cuota, 2, '.', '')));

                $root->appendChild($detalleDesglose);
            }
        }

        // // If we still don't have any data, create a default DetalleDesglose
        // if (!$detalleDesglose->hasChildNodes()) {
        //     // Create a default DetalleDesglose with basic IVA information
        //     $detalleDesglose->appendChild($this->createElement($doc, 'Impuesto', '01'));
        //     $detalleDesglose->appendChild($this->createElement($doc, 'ClaveRegimen', '01'));
        //     $detalleDesglose->appendChild($this->createElement($doc, 'CalificacionOperacion', 'S1'));
        //     $detalleDesglose->appendChild($this->createElement($doc, 'TipoImpositivo', '0'));
        //     $detalleDesglose->appendChild($this->createElement($doc, 'BaseImponibleOimporteNoSujeto', '0'));
        //     $detalleDesglose->appendChild($this->createElement($doc, 'CuotaRepercutida', '0'));
        // }

        $root->appendChild($detalleDesglose);
        return $root;
    }

    public static function fromDOMElement(\DOMElement $element): self
    {
        $desglose = new self();
        
        // Parse DesgloseFactura
        $desgloseFacturaElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseFactura')->item(0);
        if ($desgloseFacturaElement) {
            $desgloseFactura = [];
            foreach ($desgloseFacturaElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseFactura[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseFactura($desgloseFactura);
        }

        // Parse DesgloseTipoOperacion
        $desgloseTipoOperacionElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseTipoOperacion')->item(0);
        if ($desgloseTipoOperacionElement) {
            $desgloseTipoOperacion = [];
            foreach ($desgloseTipoOperacionElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseTipoOperacion[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseTipoOperacion($desgloseTipoOperacion);
        }

        // Parse DesgloseIVA
        $desgloseIvaElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseIVA')->item(0);
        if ($desgloseIvaElement) {
            $desgloseIva = [];
            foreach ($desgloseIvaElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseIva[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseIVA($desgloseIva);
        }

        // Parse DesgloseIGIC
        $desgloseIgicElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseIGIC')->item(0);
        if ($desgloseIgicElement) {
            $desgloseIgic = [];
            foreach ($desgloseIgicElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseIgic[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseIGIC($desgloseIgic);
        }

        // Parse DesgloseIRPF
        $desgloseIrpfElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseIRPF')->item(0);
        if ($desgloseIrpfElement) {
            $desgloseIrpf = [];
            foreach ($desgloseIrpfElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseIrpf[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseIRPF($desgloseIrpf);
        }

        // Parse DesgloseIS
        $desgloseIsElement = $element->getElementsByTagNameNS(self::XML_NAMESPACE, 'DesgloseIS')->item(0);
        if ($desgloseIsElement) {
            $desgloseIs = [];
            foreach ($desgloseIsElement->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $desgloseIs[$child->localName] = $child->nodeValue;
                }
            }
            $desglose->setDesgloseIS($desgloseIs);
        }

        return $desglose;
    }

    public function getDesgloseFactura(): ?array
    {
        return $this->desgloseFactura;
    }

    public function setDesgloseFactura(?array $desgloseFactura): self
    {
        $this->desgloseFactura = $desgloseFactura;
        return $this;
    }

    public function getDesgloseTipoOperacion(): ?array
    {
        return $this->desgloseTipoOperacion;
    }

    public function setDesgloseTipoOperacion(?array $desgloseTipoOperacion): self
    {
        $this->desgloseTipoOperacion = $desgloseTipoOperacion;
        return $this;
    }

    public function getDesgloseIVA(): ?array
    {
        return $this->desgloseIVA;
    }

    public function setDesgloseIVA(?array $desgloseIVA): self
    {
        $this->desgloseIVA = $desgloseIVA;
        return $this;
    }

    public function addDesgloseIVA(DetalleDesglose $desgloseIVA): self
    {
        $this->desgloseIVA[] = $desgloseIVA;
        return $this;
    }

    public function getDesgloseIGIC(): ?array
    {
        return $this->desgloseIGIC;
    }

    public function setDesgloseIGIC(?array $desgloseIGIC): self
    {
        $this->desgloseIGIC = $desgloseIGIC;
        return $this;
    }

    public function getDesgloseIRPF(): ?array
    {
        return $this->desgloseIRPF;
    }

    public function setDesgloseIRPF(?array $desgloseIRPF): self
    {
        $this->desgloseIRPF = $desgloseIRPF;
        return $this;
    }

    public function getDesgloseIS(): ?array
    {
        return $this->desgloseIS;
    }

    public function setDesgloseIS(?array $desgloseIS): self
    {
        $this->desgloseIS = $desgloseIS;
        return $this;
    }

    public function setDetalleDesglose(?DetalleDesglose $detalleDesglose): self
    {
        $this->detalleDesglose = $detalleDesglose;
        return $this;
    }

    public function getDetalleDesglose(): ?DetalleDesglose
    {
        return $this->detalleDesglose;
    }
} 