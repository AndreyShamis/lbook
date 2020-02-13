<?php

namespace App\Form;

use App\Entity\TestFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Filter Name',
                'help' => 'Provide name for new filter, you can add here suite name if relevant'
            ))
            ->add('suiteUuid', null, [
                'label' => 'Suite UUID',
                'help' => 'Suite UUID or "*" for ALL suites'
            ])
            ->add('testList',TextareaType::class, [
                'attr' => array('class' => 'filterTestList', 'rows' => '10'),
                'label' => 'Test/s',
            ])
            ->add('testingLevel')
            ->add('projectName', null, [
                'label' => 'Project Name',
                'help' => 'Gerrit project name like company/myproject'
            ])
            ->add('cluster', null, [
                'label' => 'Cluster',
                'help' => 'Cluster name, example: "cluster_lbk"'
            ])
            ->add('chip', null, [
                'required' => false
            ])
            ->add('platform', null, [
                'required' => false
            ])
            ->add('executionMode', null, [
                'required' => false
            ])
            ->add('branchName', null, [
                'required' => false,
                'help' => '[Branch Name (GERRIT_BRANCH) in PRE and MANIFEST_REVISION]']
            )
//            ->add('enabled')
            ->add('description', null, [
                'required' => true,
                'label' => 'Description',
                'help' => 'Provide description for this filter, this message printed with disabled message in suite'
            ])
            ->add('defectUrl')
//            ->add('createdAt')
//            ->add('updatedAt')
            ->add('issueContact')
//            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestFilter::class,
        ]);
    }
}
